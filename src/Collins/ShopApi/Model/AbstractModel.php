<?php
/**
 * @auther nils.droege@antevorte.org
 * (c) Antevorte GmbH & Co KG
 */

namespace Collins\ShopApi\Model;

use Collins\ShopApi;

abstract class AbstractModel
{
    /** @var ShopApi */
    protected static $shopApi;

    /**
     * @param ShopApi $shopApi
     */
    public static function setShopApi(ShopApi $shopApi)
    {
        self::$shopApi = $shopApi;
    }

    /**
     * @return ShopApi
     */
    public function getShopApi()
    {
        return self::$shopApi;
    }

    /**
     * @return ShopApi\Factory\ModelFactoryInterface
     */
    public function getModelFactory()
    {
        $factory = $this->getShopApi()->getModelFactory();

        return $factory;
    }
}
