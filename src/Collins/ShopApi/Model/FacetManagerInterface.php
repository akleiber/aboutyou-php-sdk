<?php
/**
 * @auther nils.droege@antevorte.org
 * (c) Antevorte GmbH & Co KG
 */

namespace Collins\ShopApi\Model;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

interface FacetManagerInterface extends EventSubscriberInterface
{
    /**
     * @param $shopApi \Collins\ShopApi
     * @return null
     */
    public function setShopApi($shopApi);

    /**
     * @param $groupId group id of a facet
     * @param $id id of the facet
     * @return \Collins\ShopApi\Model\Facet
     */
    public function getFacet($groupId, $id);
} 