<?php
/**
 * @author nils.droege@project-collins.com
 * (c) ABOUT YOU GmbH
 */

namespace Collins\ShopApi\Model\ProductSearchResult;


class PriceRange
{
    /** @var object */
    private $jsonObject;

    protected function __construct()
    {
    }

    /**
     * Expected json format
     * {
     * "count": 25138,
     * "from": 0,
     * "min": 399,
     * "max": 19999,
     * "to": 20000,
     * "total_count": 25138,
     * "total": 133930606,
     * "mean": 5327.8147028403
     * }
     *
     * @param \stdClass $jsonObject
     *
     * @return static
     */
    public static function createFromJson(\stdClass $jsonObject)
    {
        $priceRange = new static();

        $priceRange->jsonObject = $jsonObject;

        return $priceRange;
    }

    /**
     * @return integer
     */
    public function getProductCount()
    {
        return $this->jsonObject->count;
    }

    /**
     * in euro cent
     * @return integer
     */
    public function getFrom()
    {
        return $this->jsonObject->from;
    }

    /**
     * in euro cent
     * @return integer
     */
    public function getTo()
    {
        return isset($this->jsonObject->to) ? $this->jsonObject->to : null;
    }

    /**
     * in euro cent
     * @return integer
     */
    public function getMin()
    {
        return isset($this->jsonObject->min) ? $this->jsonObject->min : null;
    }

    /**
     * in euro cent
     * @return integer
     */
    public function getMax()
    {
        return isset($this->jsonObject->max) ? $this->jsonObject->max : null;
    }

    /**
     * in euro cent
     * @return integer
     */
    public function getMean()
    {
        return (int)round($this->jsonObject->mean);
    }

    /**
     * sum over all product min prices in this range
     * @return integer
     */
    public function getSum()
    {
        return $this->jsonObject->total;
    }
}