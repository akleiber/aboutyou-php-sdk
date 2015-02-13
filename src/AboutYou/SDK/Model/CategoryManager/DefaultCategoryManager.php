<?php
/**
 * @author nils.droege@aboutyou.de
 * (c) ABOUT YOU GmbH
 */

namespace AboutYou\SDK\Model\CategoryManager;

use Aboutyou\Common\Cache\CacheProvider;
use AboutYou\SDK\Factory\ModelFactoryInterface;
use AboutYou\SDK\Model\Category;

class DefaultCategoryManager implements CategoryManagerInterface
{
    const DEFAULT_CACHE_DURATION = 7200;

    /** @var Category[] */
    protected $categories;

    /** @var integer[] */
    protected $parentChildIds;

    /** @var Cache */
    protected $cache;

    protected $cacheKey;

    /**
     * @param string $appId This must set, when you use more then one instances with different apps
     * @param CacheProvider $cache
     */
    public function __construct(CacheProvider $cache = null, $appId = '')
    {
        $this->cache = $cache;
        $this->cacheKey = 'AY:SDK:' . $appId . ':categories';

        $this->loadCachedCategories();
    }

    public function loadCachedCategories()
    {
        if ($this->cache) {
            $result = $this->cache->fetch($this->cacheKey);
            if (isset($result['categories']) && isset($result['parentChildIds'])) {
                $this->categories = $result['categories'];
                $this->parentChildIds = $result['parentChildIds'];
            }
        }
    }

    public function cacheCategories()
    {
        if ($this->cache) {
            $this->cache->save($this->cacheKey, [
                'categories' => $this->categories,
                'parentChildIds' => $this->parentChildIds
            ], self::DEFAULT_CACHE_DURATION);
        }
    }

    public function clearCache()
    {
        if ($this->cache) {
            $this->cache->delete($this->cacheKey);
        }
    }

    /**
     * @param \stdObject $jsonObject
     * @param ModelFactoryInterface $factory
     *
     * @return $this
     */
    public function parseJson($jsonObject, ModelFactoryInterface $factory)
    {
        $this->categories = array();
        $this->parentChildIds = array();

        if(isset($jsonObject->parent_child)) {
            // this hack converts the array keys to integers, otherwise $this->parentChildIds[$id] fails
            $this->parentChildIds = json_decode(json_encode($jsonObject->parent_child), true);

            foreach ($jsonObject->ids as $id => $jsonCategory) {
                $this->categories[$id] = $factory->createCategory($jsonCategory, $this);
            }
        }

        $this->cacheCategories();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isEmpty()
    {
        return $this->categories === null;
    }

    /**
     * {@inheritdoc}
     */
    public function getCategoryTree($activeOnly = Category::ACTIVE_ONLY)
    {
        return $this->getSubCategories(0, $activeOnly);
    }

    /**
     * {@inheritdoc}
     */
    public function getCategory($id)
    {
        if (!isset($this->categories[$id])) {
            return null;
        }

        return $this->categories[$id];
    }

    /**
     * {@inheritdoc}
     */
    public function getCategories(array $ids, $activeOnly = Category::ACTIVE_ONLY)
    {
        if (empty($this->categories)) {
            return array();
        }

        $categories = array();
        foreach ($ids as $id) {
            if (isset($this->categories[$id])) {
                $category = $this->categories[$id];
                if ($activeOnly === Category::ALL || $category->isActive()) {
                    $categories[$id] = $category;
                }
            }
        }

        return $categories;
    }

    public function getAllCategories()
    {
        return $this->categories;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubCategories($id, $activeOnly = Category::ACTIVE_ONLY)
    {
        if (!isset($this->parentChildIds[$id])) {
            return array();
        }

        $ids = $this->parentChildIds[$id];

        return $this->getCategories($ids, $activeOnly);
    }

    /**
     * {@inheritdoc}
     */
    public function getFirstCategoryByName($name, $activeOnly = Category::ACTIVE_ONLY)
    {
        foreach ($this->categories as $category) {
            if ($category->getName() === $name && ($activeOnly === Category::ALL || $category->isActive())) {
                return $category;
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getCategoriesByName($name, $activeOnly = Category::ACTIVE_ONLY)
    {
        $categories = array_filter($this->categories, function ($category) use ($name, $activeOnly) {
            return (
                $category->getName() === $name
                && ($activeOnly === Category::ALL || $category->isActive())
            );
        });

        return $categories;
    }
}
