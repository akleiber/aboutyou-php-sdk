<?php
/**
 * @author nils.droege@project-collins.com
 * (c) Collins GmbH & Co KG
 */

namespace Collins\ShopApi\Model;


class ProductsResult extends AbstractProductsResult
{
    protected $productsNotFound = array();

    public function fromJson(\stdClass $jsonObject)
    {
        $this->pageHash = isset($jsonObject->pageHash) ? $jsonObject->pageHash : null;

        $factory = $this->getModelFactory();

        if (isset($jsonObject->ids)) {
            foreach ($jsonObject->ids as $key => $jsonProduct) {
                if (isset($jsonProduct->error_code)) {
                    $this->productsNotFound[] = $key;
                    continue;
                }
                $this->products[$key] = $factory->createProduct($jsonProduct);
            }
        }
    }

    /**
     * @return array of product ids
     */
    public function getProductsNotFound()
    {
        return $this->productsNotFound;
    }
}