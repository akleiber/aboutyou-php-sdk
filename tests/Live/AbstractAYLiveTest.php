<?php

namespace AboutYou\SDK\Test\Live;

use AboutYou\SDK\Criteria;

abstract class AbstractAYLiveTest extends \AboutYou\SDK\Test\AYTest
{
    private $ay;
    private $config;
    
    /**
     * @return []
     */
    protected function getConfig()
    {
        if (!isset($this->config)) {            
            $path = dirname(__FILE__) . '/config/config.ini';
 
            if (!file_exists($path)) {
                throw new \ErrorException('You need to create a config file in config/config.ini');
            }
            
            $this->config = parse_ini_file('config/config.ini');            
        }
        
        return $this->config;
    }
    
    /**
     * @return \AY
     */
    protected function getAY(
        ResultFactoryInterface $resultFactory = null,
        LoggerInterface $logger = null,
        $facetManagerCache = null
    )
    {
        $config = $this->getConfig();
        
        if (!isset($this->ay)) {
            $this->ay = new \AY($config['user'], $config['password'], $config['endpoint'], $resultFactory, $logger, $facetManagerCache);
        }
        
        return $this->ay;
    }
    
    /**
     * @return String
     */
    protected function getSessionId()
    {
        $config = $this->getConfig();

        return $config['session_id'];
    }
    
    /**
     * @param int $offset
     * @return AboutYou\SDK\Model\Product
     */
    public function getProduct($offset = 1, array $fields = null)
    {
        if ($offset < 1) {
            $offset = 1;
        }

        if ($fields === null) {
            $fields = array(\AboutYou\SDK\Criteria\ProductFields::DEFAULT_VARIANT);
        }
        
        $api = $this->getAY();
        
        $criteria = $this->getSearchCriteria();
        $criteria->setLimit(1, $offset);
        $criteria->selectProductFields($fields);
        
        $result = $api->fetchProductSearch($criteria);
        $products = $result->getProducts();
        
        return $products[0];
    }    
  
    /**
     * @return \AboutYou\SDK\Criteria\ProductSearchCriteria
     */
    protected function getSearchCriteria()
    {
        $criteria = new Criteria\ProductSearchCriteria('123456');
        
        return $criteria;
    }
    
    protected function getVariantId($index)
    {
        $product = $this->getProduct($index);
        
        return $product->getDefaultVariant()->getId();
    }
    
    protected function getProductId()
    {        
        return (int) $this->getProduct()->getId();
    }
}
