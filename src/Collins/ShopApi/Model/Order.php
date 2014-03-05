<?php
/**
 * @auther nils.droege@antevorte.org
 * (c) Antevorte GmbH & Co KG
 */

namespace Collins\ShopApi\Model;

class Order
{
    /** @var string */
    protected $id;

    /** @var Basket */
    protected $basket;

    public function __construct($id, Basket $basket)
    {
        $this->id     = $id;
        $this->basket = $basket;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Basket
     */
    public function getBasket()
    {
        return $this->basket;
    }
} 