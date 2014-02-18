<?php
/**
 * @auther nils.droege@antevorte.org
 * (c) Antevorte GmbH & Co KG
 */

namespace Collins\ShopApi\Model;

use Collins\ShopApi;
use Collins\ShopApi\Exception\MalformedJsonException;

class Product
{
    /** @var integer */
    protected $id;

    /** @var string */
    protected $name;

    /** @var mixed */
    protected $isSale;

    /** @var boolean */
    protected $isActive;

    /** @var string */
    protected $descriptionShort;

    /** @var string */
    protected $descriptionLong;

    /** @var integer */
    protected $minPrice;

    /** @var integer */
    protected $maxPrice;

    /** @var integer */
    protected $brandId;

    /** @var array */
    protected $categoryIds;

    /** @var integer[] */
    protected $attributeIds;

    /** @var Image */
    protected $defaultImage;

    /** @var Variant */
    protected $defaultVariant;

    /** @var Variant */
    protected $selectedVariant;

    /** @var Variant[] */
    protected $variants;

    /** @var Product[] */
    protected $styles;

    /** @var ProductAttributes */
    protected $attributes;

    /** @var Category */
    protected $category;

    public function __construct($jsonObject)
    {
        $this->fromJson($jsonObject);
    }

    public function fromJson($jobj)
    {
        // these are required fields
        if (!isset($jobj->id) || !isset($jobj->name)) {
            throw new MalformedJsonException();
        }
        $this->id   = $jobj->id;
        $this->name = $jobj->name;

        $this->isSale            = isset($jobj->sale) ? $jobj->sale : false;
        $this->descritptionShort = isset($jobj->description_short) ? $jobj->description_short : '';
        $this->descriptionLong   = isset($jobj->description_long) ? $jobj->description_long : '';
        $this->isActive          = isset($jobj->active) ? $jobj->active : true;
        $this->brandId           = isset($jobj->brand_id) ? $jobj->brand_id : null;

        $this->defaultImage   = isset($jobj->default_image) ? new Image($jobj->default_image) : null;
        $this->defaultVariant = isset($jobj->default_variant) ? new Variant($jobj->default_variant) : null;

        $this->variants     = self::parseVariants($jobj);
        $this->styles       = self::parseStyles($jobj);
        $this->categoryIds  = self::parseCategoryIds($jobj);
        $this->attributeIds = self::parseAttributeIds($jobj);
    }

    protected static function parseVariants($jobj)
    {
        $variants = [];
        if (!empty($jobj->variants)) {
            foreach ($jobj->variants as $variant) {
                $variants[$variant->id] = new Variant($variant);
            }
        }

        return $variants;
    }

    protected static function parseStyles($jobj)
    {
        $styles = [];
        if (!empty($jobj->styles)) {
            foreach ($jobj->styles as $style) {
                $styles[] = new Product($style);
            }
        }

        return $styles;
    }

    protected static function parseCategoryIds($jobj)
    {
        $cIds = [];
        foreach (get_object_vars($jobj) as $name => $aa) {
            if (strpos($name, 'categories') !== 0) {
                continue;
            }
            $cIds = $aa;
        }

        return $cIds;
    }

    protected static function parseAttributeIds($jobj)
    {
        $ids = [];
        if (!empty($jobj->attributes_merged)) {
            foreach ($jobj->attributes_merged as $group => $aIds) {
                $gid = substr($group, 11); // rm prefix "attributs_"
                $ids[$gid] = $aIds;
            }
        }

        return $ids;
    }

    /**
     * @return string|null
     */
    public function getDescriptionLong()
    {
        return $this->descriptionLong;
    }

    /**
     * @return string|null
     */
    public function getDescriptionShort()
    {
        return $this->descritptionShort;
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return boolean
     */
    public function isActive()
    {
        return $this->isActive;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return boolean
     */
    public function isSale()
    {
        return $this->isSale;
    }

    /**
     * return integer|null in euro cent
     */
    public function getMinPrice()
    {
        return $this->minPrice;
    }

    protected function generateAttributes()
    {
        $this->attributes = new ProductAttributes($this->attributeIds);
    }

    /**
     * @return ProductAttributes|null
     */
    public function getAttributes()
    {
        if (!$this->attributes) {
            $this->generateAttributes();
        }

        return $this->attributes;
    }

    /**
     * @return integer[]
     */
    public function getAttributeIds()
    {
        return $this->attributeIds;
    }

    /**
     * @return array
     */
    public function getCategoryIds()
    {
        return $this->categoryIds;
    }

    /**
     * Get product category.
     *
     * @return Category
     */
    public function getCategory()
    {
        //TODO: refactor
        $api = ShopApi::getCurrentApi();

        if (!$this->category) {
            foreach ($this->categoryIds as $ids) {
                if ($ids) {
                    $categories = $api->fetchCategoriesByIds($ids)->getCategories();
                    $firstId = array_shift($ids);
                    $firstCategory = $categories[$firstId];
                    if ($firstCategory && $firstCategory->isActive()) {
                        $this->category = $firstCategory;
                        foreach ($ids as $id) {
                            if (!$categories[$id]->isActive()) {
                                break;
                            }
                            $this->category = $categories[$id];
                        }
                        break;
                    }
                }
            }
        }
        return $this->category;
    }

    /**
     * Get attributes of given group id.
     *
     * @param integer $groupId The group id.
     *
     * @return \Collins\ShopApi\Model\Attribute[]
     */
    public function getGroupAttributes($groupId)
    {
        $group = $this->getAttributes()->getGroup($groupId);
        if ($group) {
            return $group->getAttributes();
        }
        return [];
    }

    public function fetchCategories()
    {
    }

    /**
     * @return Image|null
     */
    public function getDefaultImage()
    {
        return $this->defaultImage;
    }

    /**
     * @return integer|null
     */
    public function getBrandId()
    {
        return $this->brandId;
    }

    /**
     * @return \Collins\ShopApi\Model\Attribute
     */
    public function getBrand()
    {
        $key = Attribute::uniqueKey(ShopApi\Constants::FACET_BRAND, $this->brandId);

        return $this->getAttributes()->getAttributeByKey($key);
    }

    /**
     * @return Variant|null
     */
    public function getDefaultVariant()
    {
        return $this->defaultVariant;
    }

    /**
     * @return Variant[]
     */
    public function getVariants()
    {
        return $this->variants;
    }

    /**
     * @return Product[]
     */
    public function getStyles()
    {
        return $this->styles;
    }

    /**
     * @return integer|null
     */
    public function getMaxPrice()
    {
        return $this->maxPrice;
    }

    /**
     * Get variant by id.
     *
     * @param integer $variantId The variant id.
     *
     * @return Variant
     */
    public function getVariantById($variantId)
    {
        if (isset($this->variants[$variantId])) {
            return $this->variants[$variantId];
        }
        return null;
    }

    /**
     * Select a variant.
     *
     * @param integer $variantId The variant id.
     *
     * @return void
     */
    public function selectVariant($variantId)
    {
        $this->selectedVariant = $this->getVariantById($variantId);
    }

    /**
     * Get the selected or default variant.
     *
     * @return Variant
     */
    public function getSelectedVariant()
    {
        if( $this->selectedVariant ) {
            return $this->selectedVariant;
        }
        return $this->defaultVariant;
    }
}
