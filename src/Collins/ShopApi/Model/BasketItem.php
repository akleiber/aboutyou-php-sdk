<?php
namespace Collins\ShopApi\Model;

/**
 *
 */
class BasketItem extends AbstractModel
{
    /**
     * @var object
     */
    protected $jsonObject = null;

    /**
     * @var Product
     */
    private $product = null;

    /**
     * @var Variant
     */
    private $variant = null;

    /**
     * Constructor.
     *
     * @param object $jsonObject The basket data.
     */
    public function __construct($jsonObject)
    {
        $this->jsonObject = $jsonObject;
    }

    /**
     * Get the total price.
     *
     * @return integer
     */
    public function getTotalPrice()
    {
        return $this->jsonObject->total_price;
    }

    /**
     * Get the unit price.
     *
     * @return integer
     */
    public function getUnitPrice()
    {
        return $this->jsonObject->unit_price;
    }

    /**
     * Get the amount of items.
     *
     * @return integer
     */
    public function getAmount()
    {
        return $this->jsonObject->amount;
    }

    /**
     * Get the tax.
     *
     * @return integer
     */
    public function getTax()
    {
        return $this->jsonObject->tax;
    }

    /**
     * Get the tax.
     *
     * @return integer
     */
    public function getVat()
    {
        return $this->jsonObject->total_vat;
    }


    /**
     * Get the variant old price in euro cents.
     *
     * @return integer
     */
    public function getOldPrice()
    {
        return $this->getVariant()->getOldPrice();
    }

    /**
     * Get the product.
     *
     * @return Product
     */
    public function getProduct()
    {
        if (!$this->product) {
            $this->product = $this->getModelFactory()->createProduct($this->jsonObject->product);
        }

        return $this->product;
    }

    /**
     * Get the product variant.
     *
     * @return Variant
     */
    public function getVariant()
    {
        if (!$this->variant) {
            $this->variant = $this->getProduct()->getVariantById($this->jsonObject->id);
        }

        return $this->variant;
    }
}