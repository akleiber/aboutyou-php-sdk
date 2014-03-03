<?php
/**
 * @auther nils.droege@antevorte.org
 * (c) Antevorte GmbH & Co KG
 */

namespace Collins\ShopApi\Model;


class Variant extends AbstractModel
{
    protected $jsonObject;

    /** @var Image[]|null */
    protected $images = null;

    /** @var FacetGroupSet */
    protected $facetGroups;

    /**
     * @var Image
     */
    protected $selectedImage = null;

    public function __construct($jsonObject)
    {
        $this->fromJson($jsonObject);
    }

    public function fromJson($jsonObject)
    {
        $this->jsonObject = $jsonObject;
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->jsonObject->id;
    }

    /**
     * @return Image[]
     */
    public function getImages()
    {
        // parse lazy
        if ($this->images === null) {
            $this->images = [];
            if (!empty($this->jsonObject->images)) {
                $factory = $this->getModelFactory();

                foreach ($this->jsonObject->images as $image) {
                    $this->images[] = $factory->createImage($image);
                }
            }
            unset($this->jsonObject->images); // free memory
        }

        return $this->images;
    }

    /**
     * Get image by given hash.
     *
     * @param string $hash The image hash.
     *
     * @return Image
     */
    public function getImageByHash($hash)
    {
        $images = $this->getImages();
        foreach ($images as $image) {
            if ($image->getHash() == $hash) {
                return $image;
            }
        }
        if (isset($images[0])) {
            return $images[0];
        }
        return null;
    }

    /**
     * Select a specific image.
     *
     * @param string $hash The image hash or null for default image.
     *
     * @return void
     */
    public function selectImage($hash)
    {
        if ($hash) {
            $this->selectedImage = $this->getImageByHash($hash);
        } else {
            $this->selectedImage = null;
        }
    }

    /**
     * Get selected or default image.
     *
     * @return Image
     */
    public function getImage()
    {
        if ($this->selectedImage) {
            return $this->selectedImage;
        } else {
            $images = $this->getImages();
            if (isset($images[0])) {
                return $images[0];
            }
        }
        return null;
    }

    /**
     * @return string
     */
    public function getEan()
    {
        return $this->jsonObject->ean;
    }

    /**
     * @return boolean
     */
    public function isDefault()
    {
        return $this->jsonObject->default;
    }

    /**
     * Returns the price in euro cent
     *
     * @return integer
     */
    public function getPrice()
    {
        return $this->jsonObject->price;
    }

    /**
     * return integer in euro cent
     */
    public function getOldPrice()
    {
        return $this->jsonObject->old_price;
    }

    /**
     * return integer in euro cent
     */
    public function getRetailPrice()
    {
        return $this->jsonObject->retail_price;
    }

    /**
     * Returns the unstructured additional info
     *
     * return object|null
     */
    public function getAdditionalInfo()
    {
        return
            isset($this->jsonObject->additional_info) ?
            $this->jsonObject->additional_info :
            null
        ;
    }

    /**
     * @return interger
     */
    public function getMaxQuantity()
    {
        return $this->jsonObject->quantity;
    }

    protected static function parseFacetIds($jsonObject)
    {
        $ids = [];
        if (!empty($jsonObject->attributes)) {
            foreach ($jsonObject->attributes as $group => $aIds) {
                $gid = substr($group, 11); // rm prefix "attributs_"
                $ids[$gid] = $aIds;
            }
        }

        return $ids;
    }

    protected function generateFacetGroupSet()
    {
        $ids = self::parseFacetIds($this->jsonObject);
        $this->facetGroups = new FacetGroupSet($ids);
    }

    /**
     * @return FacetGroupSet
     */
    public function getFacetGroupSet()
    {
        if (!$this->facetGroups) {
            $this->generateFacetGroupSet();
        }

        return $this->facetGroups;
    }

    /**
     * @param integer $groupId
     *
     * @return FacetGroup|null
     */
    public function getFacetGroup($groupId)
    {
        $groups = $this->getFacetGroupSet();

        return $groups->getGroup($groupId);
    }

}