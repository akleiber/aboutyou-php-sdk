<?php
/**
 * @auther nils.droege@antevorte.org
 * (c) Antevorte GmbH & Co KG
 */

namespace Collins\ShopApi\Model;


interface FacetUniqueKeyInterface
{
    /**
     * @return string
     */
    public function getUniqueKey();
}