<?php
/**
 * @author nils.droege@aboutyou.de
 * (c) ABOUT YOU GmbH
 */

namespace AboutYou\SDK\Model;

use AboutYou\SDK\Factory\ModelFactoryInterface;
use AboutYou\SDK;

class ProductsResult extends AbstractProductsResult
{
    /** @var integer[] */
    protected $idsNotFound = array();

    /**
     * @param \stdClass $jsonObject
     * @param ModelFactoryInterface $factory
     *
     * @return static
     */
    public static function createFromJson(\stdClass $jsonObject, ModelFactoryInterface $factory)
    {
        $productsResult = new static();

        $productsResult->pageHash = isset($jsonObject->pageHash) ? $jsonObject->pageHash : null;

        if (isset($jsonObject->ids)) {
            foreach ($jsonObject->ids as $key => $jsonProduct) {
                if (isset($jsonProduct->error_code)) {
                    $productsResult->idsNotFound[] = $key;
                    $productsResult->errors[]      = $jsonProduct;
                    continue;
                }
                $productsResult->products[$key] = $factory->createProduct($jsonProduct);
            }
        }

        return $productsResult;
    }

    /**
     * @return integer[] array of product ids
     */
    public function getProductsNotFound()
    {
        return $this->idsNotFound;
    }
}