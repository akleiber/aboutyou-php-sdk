<?php
/**
 * @auther nils.droege@antevorte.org
 * (c) Antevorte GmbH & Co KG
 */

namespace Collins\ShopApi\Model\ProductSearchResult;


class TermCount
{
    /** @var integer */
    protected $count;

    /** @var integer */
    protected $entityId;

    /**
     * @param integer $entityId
     * @param integer $count
     */
    public function __construct($entityId, $count)
    {
        $this->entityId = $entityId;
        $this->count    = $count;
    }
} 