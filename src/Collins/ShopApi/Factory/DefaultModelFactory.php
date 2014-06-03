<?php
/**
 * @author nils.droege@project-collins.com
 * (c) Collins GmbH & Co KG
 */

namespace Collins\ShopApi\Factory;

use Collins\ShopApi;
use Collins\ShopApi\Model\FacetManager\FacetManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\GenericEvent;

class DefaultModelFactory implements ModelFactoryInterface
{
    /** @var ShopApi */
    protected $shopApi;

    /** @var FacetManagerInterface */
    protected $facetManager;

    /** @var EventDispatcher */
    protected $eventDispatcher;

    /** @var  CategoryManagerInterface */
    protected $categoryManager;

    /**
     * @param ShopApi $shopApi
     * @param FacetManagerInterface $facetManager
     * @param EventDispatcher $eventDispatcher
     */
    public function __construct(
        ShopApi $shopApi = null,
        FacetManagerInterface $facetManager,
        EventDispatcher $eventDispatcher
    ) {
        if (!empty($shopApi)) {
            $this->setShopApi($shopApi);
        }

        $this->eventDispatcher = $eventDispatcher;
        $this->setFacetManager($facetManager);
    }

    public function setShopApi(ShopApi $shopApi)
    {
        ShopApi\Model\Category::setShopApi($shopApi);
        ShopApi\Model\Product::setShopApi($shopApi);
        ShopApi\Model\FacetGroupSet::setShopApi($shopApi);

        $this->shopApi = $shopApi;
    }

    /**
     * @return EventDispatcher
     */
    public function getEventDispatcher()
    {
        return $this->eventDispatcher;
    }

    protected function subscribeFacetManagerEvents()
    {
        $newSubscribedEvents = $this->facetManager->getSubscribedEvents();
        if (!empty($newSubscribedEvents)) {
            $this->getEventDispatcher()->addSubscriber($this->facetManager);
        }
    }

    protected function unsubscribeFacetManagerEvents()
    {
        if (!empty($this->facetManager)) {
            $oldFacetManagerSubscribedEvents = $this->facetManager->getSubscribedEvents();
            if (!empty($oldFacetManagerSubscribedEvents)) {
                $this->getEventDispatcher()->removeSubscriber($this->facetManager);
            }
        }
    }

    /**
     * @param FacetManagerInterface $facetManager
     */
    public function setFacetManager(FacetManagerInterface $facetManager)
    {
        $this->unsubscribeFacetManagerEvents();
        $this->facetManager = $facetManager;
        $this->subscribeFacetManagerEvents();
        $this->facetManager->setShopApi($this->shopApi);
        ShopApi\Model\FacetGroupSet::setFacetManager($facetManager);
    }

    /**
     * @return ShopApi\Model\FacetManager|FacetManagerInterface
     */
    public function getFacetManager()
    {
        return $this->facetManager;
    }

    public function setCategoryManager()
    {

    }

    public function getCategoryManager()
    {
        if(!is_null($this->categoryManager)) {

        }
    }

    public function setBaseImageUrl($baseUrl)
    {
        ShopApi\Model\Image::setBaseUrl($baseUrl);
    }

    /**
     * @return ShopApi
     */
    protected function getShopApi()
    {
        return $this->shopApi;
    }

    /**
     * {@inheritdoc}
     *
     * @return ShopApi\Model\Autocomplete
     */
    public function createAutocomplete(\stdClass $jsonObject)
    {
        return ShopApi\Model\Autocomplete::createFromJson($jsonObject, $this);
    }

    /**
     * {@inheritdoc}
     *
     * @return ShopApi\Model\Basket
     */
    public function createBasket(\stdClass $jsonObject)
    {
        return ShopApi\Model\Basket::createFromJson($jsonObject, $this);
    }

    /**
     * {@inheritdoc}
     *
     * @return ShopApi\Model\Basket\BasketItem
     */
    public function createBasketItem(\stdClass $jsonObject, array $products)
    {
        return ShopApi\Model\Basket\BasketItem::createFromJson($jsonObject, $products);
    }

    /**
     * {@inheritdoc}
     *
     * @return ShopApi\Model\Basket\BasketSet
     */
    public function createBasketSet(\stdClass $jsonObject, array $products)
    {
        return ShopApi\Model\Basket\BasketSet::createFromJson($jsonObject, $this, $products);
    }

    /**
     * {@inheritdoc}
     *
     * @return ShopApi\Model\Basket\BasketSetItem
     */
    public function createBasketSetItem(\stdClass $jsonObject, array $products)
    {
        return ShopApi\Model\Basket\BasketSetItem::createFromJson($jsonObject, $products);
    }

    /**
     * {@inheritdoc}
     *
     * @return ShopApi\Model\CategoriesResult
     */
    public function createCategoriesResult(\stdClass $jsonObject, $queryParams)
    {
        return ShopApi\Model\CategoriesResult::createFromJson($jsonObject, $queryParams['ids'], $this);
    }

    /**
     * {@inheritdoc}
     *
     * @return ShopApi\Model\Category
     */
    public function createCategory(\stdClass $jsonObject, $parent = null)
    {
        return ShopApi\Model\Category::createFromJson($jsonObject, $this, $parent);
    }

    /**
     * {@inheritdoc}
     *
     * @return ShopApi\Model\CategoryTree
     */
    public function createCategoryTree($jsonArray)
    {
        return ShopApi\Model\CategoryTree::createFromJson($jsonArray, $this);
    }

    /**
     * {@inheritdoc}
     *
     * @return ShopApi\Model\Facet
     */
    public function createFacet(\stdClass $jsonObject)
    {
        return ShopApi\Model\Facet::createFromJson($jsonObject);
    }

    /**
     * {@inheritdoc}
     *
     * @return ShopApi\Model\Facet[]
     */
    public function createFacetList(array $jsonArray)
    {
        $facets = array();
        foreach ($jsonArray as $jsonFacet) {
            $facet = $this->createFacet($jsonFacet);
            $key = $facet->getUniqueKey();
            $facets[$key] = $facet;
        }

        return $facets;
    }

    /**
     * {@inheritdoc}
     *
     * @return ShopApi\Model\Facet[]
     */
    public function createFacetsList(\stdClass $jsonObject)
    {
        return $this->createFacetList($jsonObject->facet);
    }

    /**
     * {@inheritdoc}
     *
     * @return integer[]
     */
    public function createFacetTypes(array $jsonArray)
    {
        return $jsonArray;
    }

    /**
     * {@inheritdoc}
     *
     * @return ShopApi\Model\Image
     */
    public function createImage(\stdClass $jsonObject)
    {
        return ShopApi\Model\Image::createFromJson($jsonObject);
    }

    /**
     * {@inheritdoc}
     *
     * @return ShopApi\Model\Product
     */
    public function createProduct(\stdClass $jsonObject)
    {
        return ShopApi\Model\Product::createFromJson($jsonObject, $this, $this->shopApi->getAppId());
    }

    /**
     * {@inheritdoc}
     *
     * @return ShopApi\Model\Product
     */
    public function createSingleProduct(\stdClass $jsonObject)
    {
        $this->eventDispatcher->dispatch('collins.shop_api.product.create_model.before', new GenericEvent($jsonObject));

        return $this->createProduct($jsonObject);
    }

    /**
     * {@inheritdoc}
     *
     * @return ShopApi\Model\ProductsResult
     */
    public function createProductsResult(\stdClass $jsonObject)
    {
        return ShopApi\Model\ProductsResult::createFromJson($jsonObject, $this);
    }

    /**
     * {@inheritdoc}
     *
     * @return ShopApi\Model\ProductsEansResult
     */
    public function createProductsEansResult(\stdClass $jsonObject)
    {
        return ShopApi\Model\ProductsEansResult::createFromJson($jsonObject, $this);
    }

    /**
     * {@inheritdoc}
     *
     * @return ShopApi\Model\ProductSearchResult
     */
    public function createProductSearchResult(\stdClass $jsonObject)
    {
        return ShopApi\Model\ProductSearchResult::createFromJson($jsonObject, $this);
    }

    /**
     * {@inheritdoc}
     *
     * @return string[]
     */
    public function createSuggest(array $jsonArray)
    {
        return $jsonArray;
    }

    /**
     * {@inheritdoc}
     *
     * @return ShopApi\Model\Variant
     */
    public function createVariant(\stdClass $jsonObject)
    {
        return ShopApi\Model\Variant::createFromJson($jsonObject, $this);
    }

    /**
     * {@inheritdoc}
     *
     * @return ShopApi\Model\Order
     */
    public function createOrder(\stdClass $jsonObject)
    {
        $basket = $this->createBasket($jsonObject->basket);

        return new ShopApi\Model\Order($jsonObject->order_id, $basket);
    }

    /**
     * {@inheritdoc}
     *
     * @return ShopApi\Model\InitiateOrder
     */
    public function initiateOrder(\stdClass $jsonObject)
    {
        return ShopApi\Model\InitiateOrder::createFromJson($jsonObject);
    }

    /**
     * {@inheritdoc}
     *
     * @return ShopApi\Model\App[]
     */
    public function createChildApps(\stdClass $jsonObject)
    {
        $apps = array();
        foreach ($jsonObject->child_apps as $jsonApp) {
            $app = $this->createApp($jsonApp);
            $key = $app->getId();
            $apps[$key] = $app;
        }

        return $apps;
    }

    /**
     * {@inheritdoc}
     *
     * @return ShopApi\Model\App
     */
    public function createApp(\stdClass $jsonObject)
    {
        return ShopApi\Model\App::createFromJson($jsonObject);
    }

    /**
     * {@inheritdoc}
     */
    public function createFacetsCounts(\stdClass $jsonObject)
    {
        $facetsCounts = array();

        foreach ($jsonObject as $groupId => $jsonResultFacet) {
            if (!ctype_digit($groupId)) {
                continue;
            }
            $facetCounts = $this->getTermFacets($groupId, $jsonResultFacet->terms);

            $facetsCounts[$groupId] = ShopApi\Model\ProductSearchResult\FacetCounts::createFromJson(
                $groupId,
                $jsonResultFacet,
                $facetCounts
            );
        }

        return $facetsCounts;
    }

    protected function getTermFacets($groupId, array $jsonTerms)
    {
        $facetManager = $this->facetManager;

        $facetCounts = array();
        foreach ($jsonTerms as $jsonTerm) {
            $id = (int)$jsonTerm->term;
            $facet = $facetManager->getFacet($groupId, $id);
            if ($facet === null) {
                continue;
            } // TODO: Handle error, write test
            $count = $jsonTerm->count;
            $facetCounts[] = new ShopApi\Model\ProductSearchResult\FacetCount($facet, $count);
        }

        return $facetCounts;
    }

    /**
     * {@inheritdoc}
     *
     * @return ShopApi\Model\ProductSearchResult\PriceRange[]
     */
    public function createPriceRanges(\stdClass $jsonObject)
    {
        $priceRanges = array();
        foreach ($jsonObject->ranges as $range) {
            $priceRanges[] = ShopApi\Model\ProductSearchResult\PriceRange::createFromJson($range);
        }

        return $priceRanges;
    }

    /**
     * {@inheritdoc}
     *
     * @return ShopApi\Model\ProductSearchResult\SaleCounts
     */
    public function createSaleFacet(\stdClass $jsonObject)
    {
        return ShopApi\Model\ProductSearchResult\SaleCounts::createFromJson($jsonObject);
    }

    /**
     * {@inheritdoc}
     */
    public function createCategoriesFacets(array $jsonArray)
    {
        $counts = array();
        foreach ($jsonArray as $item) {
            $categoryId = $item->term;
            $counts[$categoryId] = $item->count;
        }

        // fetch all categories from API
        $flattenCategories = $this->getShopApi()->getCategoryManager()->getCategories(array_keys($counts), false);

        foreach ($flattenCategories as $id => $category) {
            $category->setProductCount($counts[$category->getId()]);
        }

        return $flattenCategories;
    }

    public function preHandleError($json, $resultKey, $isMultiRequest)
    {
        if ($resultKey === 'basket' && isset($json->order_lines)) {
            return false;
        }

        if ($isMultiRequest) {
            return new ShopApi\Model\ResultError($json);
        }

        throw new ShopApi\Exception\ResultErrorException($json);
    }
}