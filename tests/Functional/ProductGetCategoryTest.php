<?php
/**
 * @author nils.droege@project-collins.com
 * (c) Collins GmbH & Co KG
 */

namespace Collins\ShopApi\Test\Functional;

use Collins\ShopApi;
use Collins\ShopApi\Model\Product;

class ProductGetCategoryTest extends AbstractShopApiTest
{
    protected $setupCategoryManager = false;

    /** @var ShopApi */
    private $shopApi;

    public function setup()
    {
        $this->shopApi = $this->getShopApiWithResultFile('product/category.json');
        $this->shopApi->getCategoryManager(true);

    }

    public function getProduct($filename)
    {
        $json = $this->getJsonObjectFromFile('product/' . $filename);
        $product = Product::createFromJson($json, $this->shopApi->getResultFactory(), 98);

        return $product;
    }

    public function testGetCategoryIdHierachies()
    {
        $product = $this->getProduct('product-with-categories.json');

        $this->assertEquals(
            array(array(2,21),array(1,12,121),array(1,11),array(3)),
            $product->getCategoryIdHierachies()
        );
    }

    public function testGetCategoryIdsEmpty()
    {
        $product = $this->getProduct('product-with-attributes.json');

        $this->assertEquals(array(), $product->getCategoryIdHierachies());
    }

    public function testGetCategories()
    {
        $product = $this->getProduct('product-with-categories.json');
        $categories = $product->getRootCategories(false);
        $this->assertInternalType('array', $categories);
        $this->assertCount(3, $categories);

        foreach ($categories as $category) {
            $this->assertInstanceOf('Collins\\ShopApi\\Model\\Category', $category);
        }
        $this->assertEquals(2, $categories[0]->getId());
        $this->assertEquals(1, $categories[1]->getId());
        $this->assertEquals(3, $categories[2]->getId());

        $product = $this->getProduct('product-with-categories.json');
        $categories = $product->getRootCategories();
        $this->assertInternalType('array', $categories);
        $this->assertCount(1, $categories);
        foreach ($categories as $category) {
            $this->assertInstanceOf('Collins\\ShopApi\\Model\\Category', $category);
        }
        $this->assertEquals(1, $categories[0]->getId());
    }

    public function testGetLeafCategories()
    {
        $product = $this->getProduct('product-with-categories.json');
        $categories = $product->getLeafCategories(false);
        $this->assertInternalType('array', $categories);

        $this->assertCount(4, $categories);
        foreach ($categories as $category) {
            $this->assertInstanceOf('Collins\\ShopApi\\Model\\Category', $category);
        }
        $this->assertEquals(21, $categories[0]->getId());
        $this->assertEquals(121, $categories[1]->getId());
        $this->assertEquals(11, $categories[2]->getId());
        $this->assertEquals(3, $categories[3]->getId());

        $product = $this->getProduct('product-with-categories.json');
        $categories = $product->getLeafCategories();
        $this->assertInternalType('array', $categories);
        $this->assertCount(2, $categories);
        foreach ($categories as $category) {
            $this->assertInstanceOf('Collins\\ShopApi\\Model\\Category', $category);
        }
        $this->assertEquals(21, $categories[0]->getId());
        $this->assertEquals(121, $categories[1]->getId());
    }

    public function testGetCategory()
    {
        $product = $this->getProduct('product-with-categories.json');
        $category = $product->getCategory(false);
        $this->assertInstanceOf('Collins\\ShopApi\\Model\\Category', $category);
        $this->assertEquals(21, $category->getId());

        $category = $product->getCategory();
        $this->assertInstanceOf('Collins\\ShopApi\\Model\\Category', $category);
        $this->assertEquals(21, $category->getId());
    }

    public function testGetCategoryWithLongestActivePath()
    {
        $product = $this->getProduct('product-with-categories.json');
        $category = $product->getCategoryWithLongestActivePath();
        $this->assertInstanceOf('Collins\\ShopApi\\Model\\Category', $category);
        $this->assertEquals(121, $category->getId());
    }

    public function testGetCategoryNull()
    {
        $product  = $this->getProduct('product-with-attributes.json');
        $category = $product->getCategory();
        $this->assertNull($category);
    }
}
