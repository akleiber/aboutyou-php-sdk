<?php
/**
 * @author nils.droege@project-collins.com
 * (c) Collins GmbH & Co KG
 */

namespace Collins\ShopApi\Test\Unit;

use Collins\ShopApi;
use Collins\ShopApi\Constants;

class ShopApiTest extends \PHPUnit_Framework_TestCase
{
    public function testConsturct()
    {
        $appId = '123';
        $appPassword = 'abc';
        $apiEndPoint = 'http://localhost.dev/api';        
        $cacheInterfaceImplementation = $this->getMock('\Collins\Cache\CacheInterface');
        $loggerInterfaceImplementation = $this->getMock('\Psr\Log\LoggerInterface');
        
        $shopApi = new ShopApi(
             $appId, 
             $appPassword, 
             $apiEndPoint, 
             $cacheInterfaceImplementation,
             $loggerInterfaceImplementation
        );
        
        $this->assertEquals($apiEndPoint, $shopApi->getApiEndPoint());
        $this->assertEquals($loggerInterfaceImplementation, $shopApi->getLogger());
        $this->assertEquals($cacheInterfaceImplementation, $shopApi->getCache());
         /**
         * relies on internal configuration of live image url as constant
         */
        //$this->assertEquals(ShopApi::IMAGE_URL_LIVE, $shopApi->getBaseImageUrl());
    }
    
    /**
     * Testing the constructor with setting the stage environment constant as api endpoint
     * and overwriting 
     */
    public function testConstructStageEnvironmentImageUrl()            
    {
        $appId = '123';
        $appPassword = 'abc';
        $apiEndPoint = Constants::API_ENVIRONMENT_STAGE;        
        
        $shopApi = new ShopApi(
             $appId, 
             $appPassword, 
             $apiEndPoint
        );
        //api endpoint constant is overwritten in ShopApiClient.php  setApiEndpoint
        //not asserted internal documentation 
        //$this->assertEquals($apiEndPoint, $shopApi->getApiEndPoint());
        /**
         * this assertion relies on internal implementation and relieng on live environment 
         * urls as constant, point to refactor but actually needs a test
         */
        $this->assertEquals($shopApi::IMAGE_URL_STAGE, $shopApi->getBaseImageUrl());
    }
    /**
     * 
     * @return \Collins\ShopApi
     */
    private function getTestObject()
    {
        $appId = '123';
        $appPassword = 'abc';
        $apiEndPoint = Constants::API_ENVIRONMENT_STAGE;        
        
        $shopApi = new ShopApi(
             $appId, 
             $appPassword, 
             $apiEndPoint
        );
        return $shopApi ;
    }
    
    /**
     * Test get query function and if it initialized correctly
     */
    public function testGetQuery()
    {
        $shopApi = $this->getTestObject();
        $query = $shopApi->getQuery();
        $this->assertInstanceOf('Collins\ShopApi\Query', $query);
    }
    
    /*
     * Testing getter for shop api client and if it initialized correctly
     */
    public function testGetApiClient()
    {
        $shopApi = $this->getTestObject();
        $shopApiClient = $shopApi->getApiClient();        
        $this->assertInstanceOf('Collins\ShopApi\ShopApiClient', $shopApiClient);
    }
    
    public function testGetModelFactory()
    {
        $shopApi = $this->getTestObject();
        $modelFactory = $shopApi->getResultFactory();
        $this->assertInstanceOf('Collins\ShopApi\Factory\ResultFactoryInterface', $modelFactory);
    }
    
}
 