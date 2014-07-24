<?php
/**
 * @author nils.droege@project-collins.com
 * (c) Collins GmbH & Co KG
 */

namespace Collins\ShopApi\Model;

use Collins\ShopApi\Model\CategoryManager\CategoryManagerInterface;

class CategoryTree implements \IteratorAggregate, \Countable
{
    /** @var CategoryManagerInterface */
    private $categoryManager;

    public function __construct(CategoryManagerInterface $categoryManager)
    {
        $this->categoryManager = $categoryManager;
    }

    /**
     * @param bool $activeOnly if true, then only active categories will returned, otherwise all categories
     *
     * @return array|Category[]
     */
    public function getCategories($activeOnly = true)
    {
        return $this->categoryManager->getCategoryTree($activeOnly);
    }

    /**
     * allows foreach iteration on active top categories
     *
     * {@inheritdoc}
     *
     * @return Iterator
     */
    public function getIterator() {
        return new \ArrayIterator($this->getCategories(true));
    }

    /**
     * Count of the sub categories
     *
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->$this->getCategories(false));
    }
}