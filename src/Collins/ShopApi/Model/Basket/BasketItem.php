<?php
/**
 * @auther nils.droege@project-collins.com
 * @author Christian Kilb <christian.kilb@project-collins.com>
 * (c) Collins GmbH & Co KG
 */

namespace Collins\ShopApi\Model\Basket;

/**
 * BasketItem is a class used for adding a variant item into the basket
 *
 * If you want to add a variant into a basket, you need to create an instance
 * of a BasketItem. The BasketItem represents a variant by it's variantId.
 * It can contain $additionalData that will be transmitted to the merchant untouched.
 *
 * Example usage:
 * $variantId = $variant->getId(); // $variant is instance of \Collins\ShopApi\Model\Variant
 * $basketItem = new BasketItem('my-personal-identifier', $variantId);
 * $basketItem->setAdditionalData('jeans with engraving "for you"', ['engraving_text' => 'for you']);
 * $shopApi->addItemToBasket(session_id(), $basketItem);
 *
 * @see \Collins\ShopApi\Model\Variant
 * @see \Collins\ShopApi\Model\Basket
 * @see \Collins\ShopApi
 */
class BasketItem extends BasketVariantItem implements BasketItemInterface
{
    /**
     * The ID of this basket item. You can choose this ID by yourself to identify
     * your item later.
     *
     * @var string $id ID of this basket item
     */
    protected $id;

    /**
     * Constructor.
     *
     * @param string $id
     * @param integer $variantId
     * @param array $additionalData
     */
    public function __construct($id, $variantId, array $additionalData = null)
    {
        $this->id = $id;
        parent::__construct($variantId, $additionalData);
    }

    /**
     * @param object $jsonObject The basket data.
     * @param Product[] $products
     *
     * @return BasketItem
     */
    public static function createFromJson($jsonObject, array $products)
    {
        $item = new static($jsonObject->id, $jsonObject->variant_id, isset($jsonObject->additional_data) ? (array)$jsonObject->additional_data : null);
        $item->parseErrorResult($jsonObject);

        $item->jsonObject = $jsonObject;
        
        if (!empty($products) && $products[$jsonObject->product_id]) {
            $item->setProduct($products[$jsonObject->product_id]);
        }
        unset($jsonObject->id, $jsonObject->variant_id, $jsonObject->additional_data, $jsonObject->product_id);

        return $item;
    }

    public function getId()
    {
        return $this->id;
    }
}