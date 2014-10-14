<?php
/**
 * @author nils.droege@project-collins.com
 * (c) Collins GmbH & Co KG
 */

namespace Collins\ShopApi\Test\Unit\Model;

use Aboutyou\Common\Cache\ArrayCache;
use Collins\ShopApi\Model\Category;
use Collins\ShopApi\Model\CategoryManager\DefaultCategoryManager;

class DefaultCategoryManagerTest extends AbstractModelTest
{
    public function testParseJson()
    {
        $factory = $this->getModelFactory();

        $categoryManager = new DefaultCategoryManager();
        $factory->setCategoryManager($categoryManager);
        $jsonObject = $this->getJsonObject('category-tree-v2.json');
        $categoryManager->parseJson($jsonObject, $factory);

        return $categoryManager;
    }

    /**
     * @depends testParseJson
     */
    public function testGetCategory(DefaultCategoryManager $categoryManager)
    {
        $unknownId = 1;
        $category = $categoryManager->getCategory($unknownId);
        $this->assertNull($category);

        $knownId = 74415;
        $category = $categoryManager->getCategory($knownId);
        $this->assertInstanceOf('\\Collins\\ShopApi\\Model\\Category', $category);
        $this->assertEquals($knownId, $category->getId());
    }

    /**
     * @depends testParseJson
     */
    public function testGetCategories(DefaultCategoryManager $categoryManager)
    {
        $unknownId = 1;
        $categories = $categoryManager->getCategories(array($unknownId));
        $this->assertCount(0, $categories);

        $knownId = 74415;
        $categories = $categoryManager->getCategories(array($unknownId, $knownId));
        $this->assertCount(1, $categories);
        foreach ($categories as $category) {
            $this->assertInstanceOf('\\Collins\\ShopApi\\Model\\Category', $category);
        }
        $this->checkMainCategory($category);
    }

    public function testGetCategoriesIfEmpty()
    {
        $factory = $this->getModelFactory();

        $categoryManager = new DefaultCategoryManager();
        $factory->setCategoryManager($categoryManager);

        $categories = $categoryManager->getCategories(array(1));
        $this->assertInternalType('array', $categories);
        $this->assertCount(0, $categories);
    }

    /**
     * @depends testParseJson
     */
    public function testGetCategoryTree(DefaultCategoryManager $categoryManager)
    {
        $categories = $categoryManager->getCategoryTree();
        $this->assertCount(2, $categories);
        foreach ($categories as $category) {
            $this->assertInstanceOf('\\Collins\\ShopApi\\Model\\Category', $category);

            $this->checkMainCategory($category);
        }

        return $categories;
    }

    /**
     * @depends testParseJson
     */
    public function testGetSubCategories(DefaultCategoryManager $categoryManager)
    {
        $unknownId = 1;
        $subCategories = $categoryManager->getSubCategories($unknownId);
        $this->assertCount(0, $subCategories);

        $knownId = 74415;
        $subCategories = $categoryManager->getSubCategories($knownId);
        $this->assertCount(3, $subCategories);
        foreach ($subCategories as $subCategory) {
            $this->assertInstanceOf('\\Collins\\ShopApi\\Model\\Category', $subCategory);
        }
    }

    private function checkMainCategory(Category $category)
    {
        $subCategories = $category->getSubCategories();
        $this->assertCount(3, $subCategories);

        foreach ($subCategories as $subCategory) {
            $this->assertInstanceOf('\\Collins\\ShopApi\\Model\\Category', $subCategory);
            $this->assertEquals($category, $subCategory->getParent());
        }
    }

    /**
     * @param Category[] $categories
     *
     * @depends testGetCategoryTree
     */
    public function testCategoryTreeHierarchy($categories)
    {
        foreach ($categories as $category) {
            foreach ($category->getSubCategories() as $subCategory) {
                $this->assertEquals($category, $subCategory->getParent());
            }
        }

        $female  = array_shift($categories);
        $this->assertEquals('Frauen', $female->getName());
        $femaleCats = $female->getSubCategories();
        $subCategory = array_shift($femaleCats);
        $this->assertEquals('Shirts', $subCategory->getName());
        $this->assertEquals(74417, $subCategory->getId());
        $subCategory = array_shift($femaleCats);
        $this->assertEquals('Jeans', $subCategory->getName());
        $this->assertEquals(74419, $subCategory->getId());
        $subCategory = array_shift($femaleCats);
        $this->assertEquals('Schuhe', $subCategory->getName());
        $this->assertEquals(74421, $subCategory->getId());
        $subCategory = array_shift($femaleCats);
        $this->assertNull($subCategory);

        $male = array_shift($categories);
        $this->assertEquals('Männer', $male->getName());
        $maleCats = $male->getSubCategories();
        $subCategory = array_shift($maleCats);
        $this->assertEquals('Shirts', $subCategory->getName());
        $this->assertEquals(74418, $subCategory->getId());
        $subCategory = array_shift($maleCats);
        $this->assertEquals('Jeans', $subCategory->getName());
        $this->assertEquals(74420, $subCategory->getId());
        $subCategory = array_shift($maleCats);
        $this->assertEquals('Schuhe', $subCategory->getName());
        $this->assertEquals(74422, $subCategory->getId());
        $subCategory = array_shift($maleCats);
        $this->assertNull($subCategory);
    }

    /**
     * @depends testParseJson
     */
    public function testGetFirstCategoryByName(DefaultCategoryManager $categoryManager)
    {
        $this->assertEquals(74416, $categoryManager->getFirstCategoryByName('Männer')->getId());
        $this->assertEquals(74419, $categoryManager->getFirstCategoryByName('Jeans')->getId());
        $this->assertEquals(74417, $categoryManager->getFirstCategoryByName('Shirts')->getId());
        $this->assertNull($categoryManager->getFirstCategoryByName('Landing Page'));
        $this->assertEquals(74423, $categoryManager->getFirstCategoryByName('Landing Page', false)->getId());
        $this->assertNull($categoryManager->getFirstCategoryByName('Unknown'));
        $this->assertNull($categoryManager->getFirstCategoryByName('Unknown', Category::ALL));
    }

    /**
     * @depends testParseJson
     */
    public function testGetCategoriesByName(DefaultCategoryManager $categoryManager)
    {
        $categories = $categoryManager->getCategoriesByName('Jeans');
        $this->assertCount(2, $categories);
        $this->assertEquals(74419, reset($categories)->getId());
        $this->assertEquals(74420, end($categories)->getId());

        $categories = $categoryManager->getCategoriesByName('Landing Page');
        $this->assertCount(0, $categories);

        $categories = $categoryManager->getCategoriesByName('Landing Page', Category::ALL);
        $this->assertCount(1, $categories);

        $categories = $categoryManager->getCategoriesByName('Unknown');
        $this->assertCount(0, $categories);
    }

    public function testCacheCategories()
    {
        $factory = $this->getModelFactory();

        $cacheMock = $this->getMockForAbstractClass('Aboutyou\\Common\\Cache\\CacheProvider', array(), '', true, true, true, array('fetch', 'save'));
        $cacheMock->expects($this->atLeastOnce())
            ->method('save')
            ->with('AY:SDK:100:categories', $this->isType('array'))
        ;
        $cacheMock->expects($this->atLeastOnce())
            ->method('fetch')
            ->with('AY:SDK:100:categories')
            ->will($this->returnValue(false))
        ;

        $categoryManager = new DefaultCategoryManager($cacheMock, '100');
        $factory->setCategoryManager($categoryManager);
        $jsonObject = $this->getJsonObject('category-tree-v2.json');
        $categoryManager->parseJson($jsonObject, $factory);
    }

    public function testLoadCachedCategories()
    {
        $cache = $this->getFilledCache();

        $categoryManager = new DefaultCategoryManager($cache, '');
        $this->assertFalse($categoryManager->isEmpty());
        $this->assertCount(9, $categoryManager->getAllCategories());
    }

    private function getFilledCache()
    {
        $factory = $this->getModelFactory();
        $cache = new ArrayCache();

        $categoryManager = new DefaultCategoryManager($cache, '');
        $factory->setCategoryManager($categoryManager);
        $jsonObject = $this->getJsonObject('category-tree-v2.json');
        $categoryManager->parseJson($jsonObject, $factory);

        return $cache;
    }
}
 