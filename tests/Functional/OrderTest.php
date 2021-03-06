<?php
/**
 * @author nils.droege@aboutyou.de
 * (c) ABOUT YOU GmbH
 */

namespace AboutYou\SDK\Test\Functional;


class OrderTest extends AbstractAYTest
{
    public function testFetchOrder()
    {
        $ay = $this->getAYWithResultFile('get-order.json');

        $order = $ay->fetchOrder('1243');
        $this->assertInstanceOf('\\AboutYou\\SDK\\Model\\Order', $order);
        $this->assertEquals('123455', $order->getId());
        $basket = $order->getBasket();
        $this->assertInstanceOf('\\AboutYou\\SDK\\Model\\Basket', $basket);
    }
    
    public function testFetchOrderWithProductsWithoutCategories()
    {
        $ay = $this->getAYWithResultFile('get-order-without-categories.json');
        
        $order = $ay->fetchOrder('53574');
        $basket = $order->getBasket();
        $products = $basket->getProducts();
        $product = array_pop($products);

        $this->assertCount(0, $product->getCategories());
    }

    public function testInitiateOrderSuccess()
    {
        $ay = $this->getAYWithResultFile('initiate-order.json');
        $initiateOrder = $ay->initiateOrder(
            "abcabcabc",
            "http://somedomain.com/url"
        );
        $this->assertInstanceOf('\\AboutYou\\SDK\\Model\\InitiateOrder', $initiateOrder);
        $this->assertEquals(
            'http://ant-web1.wavecloud.de/?user_token=34f9b86d-c899-4703-b85a-3c4971601b59&app_token=10268cc8-2025-4285-8e17-bc3160865824',
            $initiateOrder->getUrl()
        );
        $this->assertEquals(
            '34f9b86d-c899-4703-b85a-3c4971601b59',
            $initiateOrder->getUserToken()
        );
        $this->assertEquals(
            '10268cc8-2025-4285-8e17-bc3160865824',
            $initiateOrder->getAppToken()
        );
    }

    public function testInitiateOrderWithCancelAndErrorUrls()
    {
        $ay = $this->getAYWithResultFile('initiate-order.json');
        $initiateOrder = $ay->initiateOrder(
            "abcabcabc",
            "http://somedomain.com/url",
            "http://somedomain.com/cancel",
            "http://somedomain.com/error"
        );
        $this->assertInternalType('string', $initiateOrder->getUrl());
    }

    /**
     * @expectedException \AboutYou\SDK\Exception\ResultErrorException
     * @expectedExceptionMessage Basket is empty: abcabcabc
     */
    public function testInitiateOrderFailedWithEmptyBasket()
    {
        $ay = $this->getAYWithResult('[
            {
                "initiate_order": {
                    "error_ident": "440db3b3-75c4-4223-b5cf-e57d37616239",
                    "error_message": [
                        "Basket is empty: abcabcabc"
                    ],
                    "error_code": 400
                }
            }
        ]');
        $initiateOrder = $ay->initiateOrder(
            "abcabcabc",
            "http://somedomain.com/url"
        );
    }

    /**
     * @expectedException \AboutYou\SDK\Exception\ResultErrorException
     * @expectedExceptionMessage success_url: u'/checkout/success' does not match '^http(s|)://'
     */
    public function testInitiateOrderFailedWithError()
    {
        $response = <<<EOS
        [{
            "initiate_order": {
                "error_message": [ "success_url: u'/checkout/success' does not match '^http(s|)://'" ],
                "error_code": 400
            }
        }]
EOS;

        $ay = $this->getAYWithResult($response);
        $initiateOrder = $ay->initiateOrder(
            "abcabcabc",
            "/somedomain.com/url"
        );
    }
}