<?php
namespace Collins\ShopApi\Test\Functional;

use Collins\ShopApi;

class CategoryTreeTest extends AbstractShopApiTest
{
    public function testFetchCategoryTree()
    {
        $shopApi = $this->getShopApiWithResultFile('category-tree-v2.json');
        $categoryTreeResult = $shopApi->fetchCategoryTree();
        $categories = $categoryTreeResult->getCategories();
        $this->assertCount(2, $categories);

        foreach ($categories as $category) {
            $this->assertInstanceOf('\Collins\ShopApi\Model\Category', $category);
            $this->assertTrue($category->isActive());
            $subCategories = $category->getSubCategories();
            $this->assertCount(3, $subCategories);
            $this->assertEquals('Shirts', $subCategories[0]->getName());
            $this->assertEquals('Jeans',  $subCategories[1]->getName());
            $this->assertEquals('Schuhe', $subCategories[2]->getName());
        }

        $this->assertArrayHasKey(74415, $categories);
        $this->assertArrayHasKey(74416, $categories);
        $this->assertArrayNotHasKey(74423, $categories);

        $category = array_shift($categories);
        $this->assertEquals(74415,    $category->getId());
        $this->assertEquals('Frauen', $category->getName());

        $category = array_shift($categories);
        $this->assertEquals(74416,    $category->getId());
        $this->assertEquals('Männer', $category->getName());
        $this->assertCount(3, $category->getSubCategories());


        $categories = $categoryTreeResult->getCategories(ShopApi\Model\Category::ALL);
        $this->assertCount(3, $categories);
        $this->assertArrayHasKey(74415, $categories);
        $this->assertArrayHasKey(74416, $categories);
        $this->assertArrayHasKey(74423, $categories);

        $category = array_pop($categories);
        $this->assertEquals(74423,    $category->getId());
        $this->assertEquals('Landing Page', $category->getName());
        $this->assertCount(0, $category->getSubCategories());

        return $categoryTreeResult;
    }

    /**
     * @depends testFetchCategoryTree
     */
    public function testProductResultIteratorInterface($categoryTreeResult)
    {
        foreach ($categoryTreeResult as $category) {
            $this->assertInstanceOf('\Collins\ShopApi\Model\Category', $category);
        }
    }

    /**
     * @depends testFetchCategoryTree
     */
    public function testProductResultCountableInterface($categoryTreeResult)
    {
        $this->assertCount(2, $categoryTreeResult);
    }
}
