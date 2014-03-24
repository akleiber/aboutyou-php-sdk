<?php
/**
 * @author nils.droege@project-collins.com
 * (c) Collins GmbH & Co KG
 */

namespace Collins\ShopApi\Model\ProductSearchResult;


class SaleCounts extends TermsCounts
{
    /** @var integer */
    protected $productCountInSale;

    /** @var integer */
    protected $productCountNotInSale;

    /**
     * @return integer
     */
    public function getProductCountInSale()
    {
        return $this->productCountInSale;
    }

    /**
     * @return integer
     */
    public function getProductCountNotInSale()
    {
        return $this->productCountNotInSale;
    }


    /**
     * {@inheritdoc}
     */
    protected function parseTerms($jsonTerms)
    {
        foreach ($jsonTerms as $term) {
            if ($term->term === "0") {
                $this->productCountNotInSale = $term->count;
            } else {
                $this->productCountInSale = $term->count;
            }
        }
    }
} 