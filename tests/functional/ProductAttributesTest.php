<?php
/**
 * @auther nils.droege@antevorte.org
 * (c) Antevorte GmbH & Co KG
 */

namespace Collins\ShopApi\Test\Functional;

use Collins\ShopApi;
use Collins\ShopApi\Model\Product;

class ProductAttributesTest extends ShopApiTest
{
    /** @var Product */
    private $product;

    /** @var ShopApi */
    private $shopApi;

    public function setup()
    {
        $json = $this->getJsonObjectFromFile('product/product-with-attributes.json');
        $this->product = new ShopApi\Model\Product($json);

        $this->shopApi = $this->getShopApiWithResultFile('facets-all.json');
    }

    public function testGetBrandWorkaround()
    {
        $json = $this->getJsonObjectFromFile('product/product-257770.json');
        $product = new ShopApi\Model\Product($json);
        $brand = $product->getBrand();

        $this->assertNotNull($brand);
        $this->assertInstanceOf('Collins\\ShopApi\\Model\\Facet', $brand);
        $this->assertEquals(0, $brand->getGroupId());
        $this->assertEquals(596, $brand->getId());
        $this->assertEquals('MARC O`POLO', $brand->getName());
        $this->assertEquals('brand', $brand->getGroupName());
    }

    public function testGetBrand()
    {
        $this->assertEquals(264, $this->product->getBrandId());

        $brand = $this->product->getBrand();

        $this->assertNotNull($brand);

        $this->assertInstanceOf('Collins\\ShopApi\\Model\\Facet', $brand);
        $this->assertEquals(0, $brand->getGroupId());
        $this->assertEquals(264, $brand->getId());
        $this->assertEquals('TOM TAILOR', $brand->getName());
        $this->assertEquals('brand', $brand->getGroupName());
    }

    public function testGetFacetGroupSet()
    {
        $attributes = $this->product->getFacetGroupSet();
        $this->assertInstanceOf('Collins\\ShopApi\\Model\\FacetGroupSet', $attributes);

        $groups = $attributes->getGroups();
        $this->assertCount(4, $groups);

        $brands = $groups[ShopApi\Constants::FACET_BRAND];
        $this->assertInstanceOf('Collins\\ShopApi\\Model\\FacetGroup', $brands);
        $this->assertEquals(0, $brands->getId());
        $this->assertEquals('brand', $brands->getName());
        $attribute = reset($brands->getFacets());
        $this->assertEquals($attribute, $this->product->getBrand());
        $this->assertEquals(0, $attribute->getGroupId());
        $this->assertEquals('brand', $attribute->getGroupName());
        $this->assertEquals(264, $attribute->getId());
        $this->assertEquals('TOM TAILOR', $attribute->getName());

        $color = $groups[ShopApi\Constants::FACET_COLOR];
        $this->assertInstanceOf('Collins\\ShopApi\\Model\\FacetGroup', $color);
        $this->assertEquals(1, $color->getId());
        $this->assertEquals('color', $color->getName());
    }

    public function testGetGroupFacets()
    {
        $colors = $this->product->getGroupFacets(ShopApi\Constants::FACET_COLOR);
        $this->assertNotNull($colors);
        $this->assertInternalType('array', $colors);
        $color = $colors[12];
        $this->assertInstanceOf('Collins\\ShopApi\\Model\\Facet', $color);
        $this->assertEquals(12, $color->getId());
        $this->assertEquals('Grau', $color->getName());
        $this->assertEquals('grau', $color->getValue());
        $this->assertEquals('color', $color->getGroupName());
    }

    public function testGetFacetGroups()
    {
        $json = json_decode(file_get_contents(__DIR__ . '/testData/product-full.json'));
        $product = new ShopApi\Model\Product($json);

        $facetGroups = $product->getFacetGroups(206);
        $this->assertCount(5, $facetGroups);
        foreach ($facetGroups as $group) {
            $this->assertInstanceOf('Collins\\ShopApi\\Model\\FacetGroup', $group);
            $this->assertEquals(206, $group->getId());
        }
    }
}
