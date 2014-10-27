<?php

namespace AboutYou\SDK\Test\Live;

/**
 * @group live
 */
class VariantTest extends \AboutYou\SDK\Test\Live\AbstractAYLiveTest
{
    public function testGetVariantById()
    {
        $ay = $this->getAY();
        $id = $this->getVariantId(1);

        $result = $ay->fetchVariantsByIds(array($id, $id * 1000));

        $this->assertInstanceOf('\\AboutYou\\SDK\\Model\\VariantsResult', $result);
        $this->assertTrue($result->hasVariantsNotFound());

        $errors = $result->getVariantsNotFound();

        $this->assertEquals($id * 1000, $errors[0]);

        $this->assertCount(1, $result->getVariantsFound());

        $variant = $result->getVariantById($id);
        $this->assertInstanceOf('\\AboutYou\\SDK\\Model\\Variant', $variant);

        if ($variant->getAboutNumber() !== null) {
            $this->assertInternalType('string', $variant->getAboutNumber());
        }

        $this->assertEquals($id, $variant->getId());
        $this->assertInstanceOf('\\AboutYou\\SDK\\Model\\Product', $variant->getProduct());
    }

    public function testGetVariantByIdWithSameProduct()
    {
        $ay = $this->getAY();

        $result = $ay->fetchVariantsByIds(array('4683343', '4683349'));

        $this->assertInstanceOf('\\AboutYou\\SDK\\Model\\VariantsResult', $result);
        $this->assertFalse($result->hasVariantsNotFound());

        $this->assertCount(2, $result->getVariantsFound());

        foreach ($result->getVariantsFound() as $variant) {
            $this->assertInstanceOf('\\AboutYou\\SDK\\Model\\Variant', $variant);
            $product = $variant->getProduct();
            $this->assertInstanceOf('\\AboutYou\\SDK\\Model\\Product', $product);

            $this->assertEquals(215114, $product->getId());
        }
    }

    public function testGetVariantByIdWithWrongIds()
    {
        $ay = $this->getAY();
        $ids = array('583336000', '58333600');

        $result = $ay->fetchVariantsByIds($ids);

        $this->assertInstanceOf('\\AboutYou\\SDK\\Model\\VariantsResult', $result);
        $this->assertTrue($result->hasVariantsNotFound());

        $errors = $result->getVariantsNotFound();

        $this->assertCount(2, $errors);

        foreach ($ids as $id) {
            $this->assertTrue(in_array($id, $errors));
        }
    }
}
