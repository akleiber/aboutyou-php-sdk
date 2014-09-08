<?php
/**
 * @author nils.droege@project-collins.com
 * (c) Collins GmbH & Co KG
 */

namespace Collins\ShopApi\Model\CategoryManager;

use Collins\ShopApi\Model\Category;

interface CategoryManagerInterface
{
    /**
     * @return boolean
     */
    public function isEmpty();

    /**
     * @param integer $id
     *
     * @return Category|null
     */
    public function getCategory($id);

    /**
     * @param integer[] $ids
     * @param boolean   $activeOnly
     *
     * @return Category[]
     */
    public function getCategories(array $ids, $activeOnly = true);

    /**
     * @param integer $id
     * @param boolean $activeOnly
     *
     * @return Category[]
     */
    public function getSubCategories($id, $activeOnly = true);

    /**
     * @param boolean $activeOnly
     *
     * @return Category[]
     */
    public function getCategoryTree($activeOnly = true);

    /**
     * @expimental
     *
     * @param string $name
     *
     * @return Category
     */
    public function getFirstCategoryByName($name, $activeOnly = true);

    /**
     * @expimental
     *
     * @param string $name
     *
     * @return Category[]
     */
    public function getCategoriesByName($name, $activeOnly = true);
}