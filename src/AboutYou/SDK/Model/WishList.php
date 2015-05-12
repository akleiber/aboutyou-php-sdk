<?php
namespace AboutYou\SDK\Model;

use AboutYou\SDK\Factory\ModelFactoryInterface;
use AboutYou\SDK\Model\WishList\WishListSet;
use AboutYou\SDK\Model\WishList\WishListItem;

/**
 *
 */
class WishList
{
    /** @var AbstractWishListItem[] */
    private $items = [];

    private $errors = [];

    /** @var integer */
    protected $uniqueVariantCount;

    /** @var Product[] */
    protected $products;

    /** @var integer */
    protected $totalPrice;

    /** @var integer */
    protected $totalNet;

    /** @var integer */
    protected $totalVat;

    /** @var boolean */
    protected $clearOnUpdate = false;

    /**
     * Constructor.
     */
    public function __construct()
    {
    }

    /**
     * @param object $jsonObject
     * @param ModelFactoryInterface $factory
     *
     * @return WishList
     */
    public static function createFromJson($jsonObject, ModelFactoryInterface $factory)
    {
        $WishList = new static();
        $WishList->totalPrice = $jsonObject->total_price;
        $WishList->totalNet   = $jsonObject->total_net;
        $WishList->totalVat   = $jsonObject->total_vat;

        $WishList->parseItems($jsonObject, $factory);

        return $WishList;
    }

    /**
     * Get the total price.
     *
     * @return integer
     */
    public function getTotalPrice()
    {
        return $this->totalPrice;
    }

    /**
     * Get the total net.
     *
     * @return integer
     */
    public function getTotalNet()
    {
        return $this->totalNet;
    }

    /**
     * Get the total vat.
     *
     * @return integer
     */
    public function getTotalVat()
    {
        return $this->totalVat;
    }

    /**
     * Get the total amount of all items.
     *
     * @return integer
     */
    public function getTotalAmount()
    {
        return count($this->items);
    }

    /**
     * Get the number of variants.
     *
     * @return integer
     */
    public function getTotalVariants()
    {
        return $this->uniqueVariantCount;
    }

    /**
     * @return boolean
     */
    public function hasErrors()
    {
        return count($this->errors) > 0;
    }

    /**
     * Returns all items with errors
     *
     * @return WishListItem[]|WishListSet[]
     */
    public function getErrors()
    {
        return $this->errors;
    }


    /**
     * Get all WishList items.
     *
     * @return WishListItem[]|WishListSet[]
     */
    public function getItems()
    {
        return array_values($this->items);
    }

    /**
     * @param $itemId
     *
     * @return WishListItem|WishListSet|null
     */
    public function getItem($itemId)
    {
        return isset($this->items[$itemId]) ?
            $this->items[$itemId] :
            null
        ;
    }

    /**
     * @return Product[]
     */
    public function getProducts()
    {
        return $this->products;
    }

    public function getCollectedItems()
    {
        $items = $this->getItems();
        $itemsMerged = [];
        foreach ($items as $item) {
            $key = $item->getUniqueKey();
            if (isset($itemsMerged[$key])) {
                $amount = $itemsMerged[$key]['amount'] + 1;
                $itemsMerged[$key] = [
                    'item' => $item,
                    'price' => $item->getTotalPrice() * $amount,
                    'amount' => $amount
                ];
            } else {
                $itemsMerged[$key] = [
                    'item' => $item,
                    'price' => $item->getTotalPrice(),
                    'amount' => 1
                ];
            }
        }

        return array_values($itemsMerged);
    }

    /**
     * build order line for update query
     * @return array
     */
    public function getOrderLinesArray()
    {
        $orderLines = [];

        foreach (array_unique($this->deletedItems) as $itemId) {
            $orderLines[] = ['delete' => $itemId];
        }

        foreach ($this->updatedItems as $item) {
            $orderLines[] = $item;
        }

        return $orderLines;
    }

    protected function parseItems($jsonObject, ModelFactoryInterface $factory)
    {
        $products = [];
        if (!empty($jsonObject->products)) {
            foreach ($jsonObject->products as $productId => $jsonProduct) {
                $products[$productId] = $factory->createProduct($jsonProduct);
            }
        }
        $this->products = $products;

        $vids = [];
        if (!empty($jsonObject->order_lines)) {
            foreach ($jsonObject->order_lines as $index => $jsonItem) {
                if (isset($jsonItem->set_items)) {
                    $item = $factory->createWishListSet($jsonItem, $products);
                } else {
                    $vids[] = $jsonItem->variant_id;
                    $item = $factory->createWishListItem($jsonItem, $products);
                }

                if ($item->hasErrors()) {
                    $this->errors[$index] = $item;
                } else {
                    $this->items[$item->getId()] = $item;
                }
            }
        }

        $vids = array_values(array_unique($vids));
        $this->uniqueVariantCount = count($vids);
    }

    /*
     * Methods to manipulate WishList
     *
     * this api is unstable method names and signatures may be changed in the future
     */

    /** @var array */
    protected $deletedItems = [];
    /** @var array */
    protected $updatedItems = [];

    /**
     * @param string $itemId
     *
     * @return $this
     */
    public function deleteItem($itemId)
    {
        $this->deletedItems[$itemId] = $itemId;

        return $this;
    }

    /**
     * @param string[] $itemIds
     *
     * @return $this
     */
    public function deleteItems(array $itemIds)
    {
        $this->deletedItems = array_merge($this->deletedItems, $itemIds);

        return $this;
    }

    /**
     * @return $this
     */
    public function deleteAllItems($delete = true)
    {
        $this->clearOnUpdate = $delete !== false || $delete !== 0;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isClearedOnUpdate()
    {
        return $this->clearOnUpdate;
    }

    /**
     * @param WishListItem $WishListItem
     * @param \DateTime $addedOn overwrites the added_on date; if given
     *
     * @return $this
     */
    public function updateItem(WishListItem $WishListItem, \DateTime $addedOn = null)
    {
        $itemId = $WishListItem->getId();

        $item = [
            'variant_id' => $WishListItem->getVariantId(),
            'app_id' => $WishListItem->getAppId()
        ];
        if ($itemId) {
            $item['id'] = $itemId;
        }

        $additionalData = $WishListItem->getAdditionalData();
        if (!empty($additionalData)) {
            $this->checkAdditionData($additionalData);
            $item['additional_data'] = (array)$additionalData;
        }

        if ($addedOn) {
            $item['added_on'] = $addedOn->format('Y-m-d');
        }

        if ($itemId) {
            $this->updatedItems[$WishListItem->getId()] = $item;
        } else {
            $this->updatedItems[] = $item;
        }

        return $this;
    }

    /**
     * @param WishListSet $WishListSet
     *
     * @return $this
     */
    public function updateItemSet(WishListSet $WishListSet)
    {
        $items = $WishListSet->getItems();

        if (empty($items)) {
            throw new \InvalidArgumentException('WishListSet needs at least one item');
        }

        $setItems = [];
        foreach ($items as $subItem) {
            $item = [
                'variant_id' => $subItem->getVariantId(),
                'app_id' => $subItem->getAppId()
            ];
            $additionalData = $subItem->getAdditionalData();
            if (!empty($additionalData)) {
                $this->checkAdditionData($additionalData);
                $item['additional_data'] = (array)$additionalData;
            }
            $setItems[] = $item;
        }

        $set = [
            'additional_data' => (array)$WishListSet->getAdditionalData(),
            'set_items' => $setItems,
        ];
        $setId = $WishListSet->getId();
        if ($setId) {
            $set['id'] = $setId;
            $this->updatedItems[$setId] = $set;
        } else {
            $this->updatedItems[] = $set;
        }

        return $this;

    }

    protected function checkAdditionData(array $additionalData = null, $imageUrlRequired = false)
    {
        if ($additionalData && !isset($additionalData['description'])) {
            throw new \InvalidArgumentException('description is required in additional data');
        }

        if (isset($additionalData['internal_infos']) && !is_array($additionalData['internal_infos'])) {
            throw new \InvalidArgumentException('internal_infos must be an array');
        }
    }
}
