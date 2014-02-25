<?php
/**
 * @auther nils.droege@antevorte.org
 * (c) Antevorte GmbH & Co KG
 */

namespace Collins\ShopApi\Criteria;

use Collins\ShopApi\Exception\InvalidParameterException;
use Collins\ShopApi\Model\FacetGroup;
use Collins\ShopApi\Model\FacetGroupSet;
use Collins\ShopApi\Model\Product;

class ProductSearchCriteria extends AbstractCriteria implements CriteriaInterface
{
    const SORT_TYPE_RELEVANCE   = 'relevance';
    const SORT_TYPE_UPDATED     = 'updated_date';
    const SORT_TYPE_CREATED     = 'created_date';
    const SORT_TYPE_MOST_VIEWED = 'most_viewed';
    const SORT_TYPE_PRICE       = 'price';

    const SORT_ASC  = 'asc';
    const SORT_DESC = 'desc';

    const FACETS_ALL = '_all';

    const FILTER_SALE          = 'sale';
    const FILTER_CATEGORY_IDS  = 'categories';
    const FILTER_PRICE         = 'prices';
    const FILTER_SEARCHWORD    = 'searchword';
    const FILTER_ATTRIBUTES    = 'facets';

    /** @var array */
    protected $filter = [];


    /** @var array */
    protected $result;


    /** @var string */
    protected $sessionId;

    /**
     * @param string $sessionId
     */
    public function __construct($sessionId)
    {
        $this->sessionId = $sessionId;
        $this->result    = [];
    }

    /**
     * Creates a new instance of this class and returns it.
     * @return ProductSearchCriteria
     */
    public static function create($sessionId)
    {
        return new self($sessionId);
    }

    /**
     * @param string $key
     * @param string $value
     *
     * @return ProductSearchCriteria
     */
    public function filterBy($key, $value)
    {
        $this->filter[$key] = $value;

        return $this;
    }

    /**
     * @param boolean|null $sale
     *    true => only sale products
     *    false => no sale products
     *    null => both (default)
     *
     * @return ProductSearchCriteria
     */
    public function filterBySale($sale)
    {
        if (!is_bool($sale)) {
            $sale = null;
        }

        return $this->filterBy(self::FILTER_SALE, $sale);
    }

    /**
     * @param string $searchword
     *
     * @return ProductSearchCriteria
     */
    public function filterBySearchword($searchword)
    {
        return $this->filterBy(self::FILTER_SEARCHWORD, $searchword);
    }

    /**
     * @param array $categoryIds array of integer
     *
     * @return ProductSearchCriteria
     */
    public function filterByCategoryIds(array $categoryIds)
    {
        return $this->filterBy(self::FILTER_CATEGORY_IDS, $categoryIds);
    }

    /**
     * @param array $attributes  array of array with group id and attribute ids
     *   for example [0 => [264]]: search for products with the brand "TOM TAILER"
     *
     * @return ProductSearchFilter
     */
    public function filterByFacetIds(array $attributes)
    {
        return $this->filterBy(self::FILTER_ATTRIBUTES, (object)$attributes);
    }

    /**
     * @param FacetGroup $facetGroup
     *
     * @return ProductSearchCriteria
     */
    public function filterByFacetGroup(FacetGroup $facetGroup)
    {
        return $this->filterBy(self::FILTER_ATTRIBUTES, (object)$facetGroup->getIds());
    }

    /**
     * @param FacetGroupSet $facetGroupSet
     *
     * @return ProductSearchCriteria
     */
    public function filterByFacetGroupSet(FacetGroupSet $facetGroupSet)
    {
        return $this->filterBy(self::FILTER_ATTRIBUTES, (object)$facetGroupSet->getIds());
    }

    /**
     * @param integer $from  must be 1 or greater
     * @param integer $to    must be 1 or greater
     *
     * @return ProductSearchCriteria
     */
    public function filterByPriceRange($from = 0, $to = 0)
    {
        settype($from, 'int');
        settype($to, 'int');

        $price = [];
        if ($from > 0) {
            $price['from'] = $from;
        }
        if ($to > 0) {
            $price['to'] = $to;
        }

        return $this->filterBy(self::FILTER_PRICE, $price);
    }

    /**
     * @param string $type
     * @param string $direction
     */
    public function sortBy($type, $direction = self::SORT_ASC)
    {
        $this->result['sort'] = [
            'by'        => $type,
            'direction' => $direction,
        ];

        return $this;
    }

    /**
     * @param integer $limit
     * @param integer $offset
     */
    public function setLimit($limit, $offset = 0)
    {
        max(min($limit, 200), 0);
        $this->result['limit'] = $limit;

        max($offset, 0);
        $this->result['offset'] = $offset;

        return $this;
    }

    /**
     * @param bool $enable
     *
     * @return $this
     */
    public function selectSaleFacets($enable = true)
    {
        if ($enable) {
            $this->result['sale'] = true;
        } else {
            unset($this->result['sale']);
        }

        return $this;
    }

    /**
     * @param bool $enable
     *
     * @return $this
     */
    public function selectPriceFacets($enable = true)
    {
        if ($enable) {
            $this->result['price'] = true;
        } else {
            unset($this->result['price']);
        }

        return $this;
    }

    /**
     * @param integer|string|FacetGroup $groupId
     * @param integer $limit
     *
     * @return $this
     */
    public function selectFacetsByGroupId($groupId, $limit)
    {
        if ($groupId instanceof FacetGroup) {
            $groupId = $groupId->getId();
        } else if ($groupId !== self::FACETS_ALL && !is_long($groupId) && !ctype_digit($groupId)) {
            throw new InvalidParameterException();
        }

        if (!isset($this->result['facets'])) {
            $this->result['facets'] = new \StdClass;
        }

        if (!isset($this->result['facets']->{$groupId})) {
            $this->result['facets']->{$groupId} = new \StdClass;
        }

        $this->result['facets']->{$groupId}->limit = $limit;

        return $this;
    }


    /**
     * @param bool $enable
     *
     * @return $this
     */
    public function selectCategoryFacets($enable = true)
    {
        if ($enable) {
            $this->result['categories'] = true;
        } else {
            unset($this->result['categories']);
        }

        return $this;
    }

    /**
     * @param integer|Product[] $ids
     *
     * @return $this
     */
    public function boostProducts(array $ids)
    {
        $ids = array_map(function($val) {
            if($val instanceof Product) {
                return $val->getId();
            }

            return intval($val);
        }, $ids);

        if (empty($this->result['boost'])) {
            unset($this->result['boost']);
        }

        $ids = array_unique(array_map('intval', $ids));
        $this->result['boost'] = $ids;

        return $this;
    }

    /**
     * @param string[] $fields
     *
     * @return $this
     */
    public function selectProductFields(array $fields)
    {
        $this->result['fields'] = array_unique($fields);

        return $this;
    }

    /**
     * @param string $sessionId
     *
     * @return $this
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $params = [
            'session_id' => $this->sessionId
        ];

        if (!empty($this->result)) {
            $params['result'] = $this->result;
        }
        if ($this->filter) {
            $params['filter'] = $this->filter;
        }

        return $params;
    }
}
