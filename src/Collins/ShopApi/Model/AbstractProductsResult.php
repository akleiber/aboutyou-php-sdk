<?php
/**
 * @author nils.droege@project-collins.com
 * (c) Collins GmbH & Co KG
 */

namespace Collins\ShopApi\Model;

use Collins\ShopApi\Factory\ModelFactoryInterface;

abstract class AbstractProductsResult extends AbstractModel implements \IteratorAggregate, \ArrayAccess, \Countable
{
    /** @var Product[] */
    protected $products;

    /** @var string */
    protected $pageHash;

    protected $factory;

    public function __construct(\stdClass $jsonObject, ModelFactoryInterface $factory)
    {
        $this->products = array();
        $this->fromJson($jsonObject, $factory);
        $this->preFetchFacets();
    }

    public function preFetchFacets()
    {
        $brandIds = array();
        $groupIds = array();
        foreach ($this->products as $product) {
            $ids = $product->getFacetIds();
            if (!$ids) break; // every product should have merged_attributes

            if ($ids[0]) {
                $brandIds[] = $ids[0];
                unset($ids[0]);
            }
            $groupIds[] = array_keys($ids);
        }

        if (!empty($brandIds)) {
            $brandIds = call_user_func_array('array_merge', $brandIds);
            $brandIds = array_unique($brandIds);
        }
        if (!empty($groupIds)) {
            $groupIds = call_user_func_array('array_merge', $groupIds);
        }
    }

    abstract protected function fromJson(\stdClass $jsonObject, ModelFactoryInterface $factory);

    /**
     * @return string
     */
    public function getPageHash()
    {
        return $this->pageHash;
    }

    /**
     * @return Product[]
     */
    public function getProducts()
    {
        return $this->products;
    }

    /*
     * Interface implementations
     */

    /**
     * allows foreach iteration over the products
     *
     * {@inheritdoc}
     *
     * @return \Iterator
     */
    public function getIterator() {
        return new \ArrayIterator($this->products);
    }

    /**
     * Tests, if a Product with this id exists
     *
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        return isset($this->products[$offset]);
    }

    /**
     * Returns the Product with that id
     *
     * {@inheritdoc}
     *
     * @return Product
     */
    public function offsetGet($offset)
    {
        return isset($this->products[$offset]) ? $this->products[$offset] : null;
    }

    /**
     * {@inheritdoc}
     *
     * throws LogicException because, it's readonly
     */
    public function offsetSet($index, $newval) {
        throw new LogicException('Attempting to write to an immutable array');
    }

    /**
     * {@inheritdoc}
     *
     * throws LogicException because, it's readonly
     */
    public function offsetUnset($index) {
        throw new LogicException('Attempting to write to an immutable array');
    }

    /**
     * Count of all fetched Products
     *
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->products);
    }
}