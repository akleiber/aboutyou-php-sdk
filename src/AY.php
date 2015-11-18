<?php
use AboutYou\SDK\Model\StylesResult;
use AuthSDK\AuthSDK;
use AuthSDK\SessionStorage;
use AboutYou\ApiErrorException;
use AboutYou\MalformedJsonException;
use AboutYou\SDK\Constants;
use AboutYou\SDK\Criteria\ProductSearchCriteria;
use AboutYou\SDK\Factory\DefaultModelFactory;
use AboutYou\SDK\Factory\ModelFactoryInterface;
use AboutYou\SDK\Model\Autocomplete;
use AboutYou\SDK\Model\Basket;
use AboutYou\SDK\Model\WishList;
use AboutYou\SDK\Model\CategoriesResult;
use AboutYou\SDK\Model\CategoryManager\CategoryManagerInterface;
use AboutYou\SDK\Model\CategoryManager\DefaultCategoryManager;
use AboutYou\SDK\Model\CategoryTree;
use AboutYou\SDK\Model\FacetManager\DefaultFacetManager;
use AboutYou\SDK\Model\FacetManager\AboutyouCacheStrategy;
use AboutYou\SDK\Model\FacetManager\FetchFacetGroupStrategy;
use AboutYou\SDK\Model\ProductsEansResult;
use AboutYou\SDK\Model\ProductSearchResult;
use AboutYou\SDK\Model\ProductsResult;
use AboutYou\SDK\Query;
use AboutYou\SDK\Client;
use Psr\Log\LoggerInterface;
use Rhumsaa\Uuid\Uuid;

/**
 * Provides access to the ABOUT YOU Frontend Platform.
 * All the public methods cover a single API query.
 *
 * @author ABOUT YOU GmbH
 */
class AY
{
    const DEVCENTER_API_URL_STAGE = 'http://devcenter-staging-www1.pub.collins.kg:81/api';
    const DEVCENTER_API_URL_SANDBOX = 'http://devcenter-staging-www1.pub.collins.kg:81/api';
    const DEVCENTER_API_URL_LIVE = 'https://developer.aboutyou.de/api';
    const IMAGE_URL_STAGE   = 'http://mndb.staging.aboutyou.de/mmdb/file';
    const IMAGE_URL_SANDBOX = 'http://mndb.sandbox.aboutyou.de/mmdb/file';
    const IMAGE_URL_LIVE    = 'http://cdn.aboutyou.de/file';

    const PRE_CACHE_FACETS   = 1;
    const PRE_CACHE_CATEGORY = 2;
    const PRE_CACHE_ALL      = 3;

    /** @var Client */
    protected $client;

    /** @var string */
    protected $baseImageUrl;

    /** @var string */
    protected static $devcenterApiUrl;

    /** @var ModelFactoryInterface */
    protected $modelFactory = null;

    /** @var LoggerInterface */
    protected $logger;

    /** @var string Constants::API_ENVIRONMENT_LIVE for live environment, Constants::API_ENVIRONMENT_STAGE for staging */
    protected $environment = Constants::API_ENVIRONMENT_LIVE;

    /** @var string */
    protected $appId;
    /** @var string */
    protected $appPassword;
    /** @var AuthSDK */
    protected $authSdk;

    /**
     * Page ID or Request ID to identify multiple requests for a single page including ajax queries
     * The id is an alhpa num string: [a-zA-Z0-9_-+]+
     *
     * @var string
     */
    protected $pageId;

    protected $cache;

    /**
     * @param string $appId
     * @param string $appPassword
     * @param string $apiEndPoint Constants::API_ENVIRONMENT_LIVE for live environment, Constants::API_ENVIRONMENT_STAGE for staging
     * @param ModelFactoryInterface $resultFactory if null it will use the DefaultModelFactory with the DefaultFacetManager
     * @param LoggerInterface $logger
     * @param \Aboutyou\Common\Cache\CacheMultiGet|\Doctrine\Common\Cache\CacheMultiGet $facetManagerCache
     */
    public function __construct(
        $appId,
        $appPassword,
        $apiEndPoint = Constants::API_ENVIRONMENT_LIVE,
        ModelFactoryInterface $resultFactory = null,
        LoggerInterface $logger = null,
        $cache = null
    ) {
        $this->client = new Client($appId, $appPassword, $apiEndPoint, $logger);
        $this->generatePageId();

        if ($cache) {
            $this->modelFactory = function ($scope) use ($cache) {
                return $scope->initDefaultFactory($cache);
            };
            $this->cache = $cache;
        }

        $this->setBaseImageUrlByApiEndPoint($apiEndPoint);

        $this->logger      = $logger;
        $this->appId       = $appId;
        $this->appPassword = $appPassword;
    }

    /**
     * @return Client
     */
    public function getApiClient()
    {
        return $this->client;
    }

    /**
     * Generates a unique ID for this request
     *
     * @param string $prefix
     */
    public function generatePageId($prefix = '')
    {
        $this->setPageId($prefix . Uuid::uuid4());
    }

    /**
     * Gets the Page Id, put this request id on your page and use it for all ajax requests
     *
     * @return string
     */
    public function getPageId()
    {
        return $this->pageId;
    }

    /**
     * Sets the page id,
     * you should overwrite the page id, on all ajax queries, to collect all requests for one page.
     *
     * @param string $pageId
     */
    public function setPageId($pageId)
    {
        $this->pageId = $pageId;
        $this->client->setPageId($pageId);
    }

    /**
     * @param string $appId        the app id for client authentication
     * @param string $appPassword  the app password/token for client authentication.
     */
    public function setAppCredentials($appId, $appPassword)
    {
        $this->appId       = $appId;
        $this->appPassword = $appPassword;
        $this->client->setAppCredentials($appId, $appPassword);
    }

    /**
     * @return string
     */
    public function getAppId()
    {
        return $this->appId;
    }

    /**
     * @return string
     */
    public function getApiEndPoint()
    {
        return $this->client->getApiEndPoint();
    }

    /**
     * @param string $apiEndPoint the endpoint can be the string 'stage' or 'live',
     *                            then the default endpoints will be used or
     *                            an absolute url
     */
    public function setApiEndpoint($apiEndPoint)
    {
        $this->client->setApiEndpoint($apiEndPoint);
    }

    /**
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->client->setLogger($logger);
    }

    /**
     * @return \Psr\Log\LoggerInterface
     */
    public function getLogger()
    {
        return $this->client->getLogger();
    }

    /**
     * @param string $logTemplate
     *
     * @see http://api.guzzlephp.org/class-Guzzle.Log.MessageFormatter.html
     */
    public function setLogTemplate($logTemplate)
    {
        $this->client->setLogTemplate($logTemplate);
    }

    /**
     * @return string
     */
    public function getLogTemplate()
    {
        return $this->client->getLogTemplate();
    }

    /**
     * @param null|false|string $baseImageUrl null will reset to the default url, false to get relative urls, otherwise the url prefix
     */
    public function setBaseImageUrl($baseImageUrl = null)
    {
        if ($baseImageUrl === null) {
            $this->baseImageUrl = self::IMAGE_URL_LIVE;
        } else if (is_string($baseImageUrl)) {
            $this->baseImageUrl = rtrim($baseImageUrl, '/');
        } else {
            $this->baseImageUrl = '';
        }

        if ($this->modelFactory && $this->modelFactory instanceof ModelFactoryInterface) {
            $this->getResultFactory()->setBaseImageUrl($this->baseImageUrl);
        }
    }

    /**
     * @param null|false|string $devCenterApiURL null will reset to the default url, false to get relative urls, otherwise the url prefix
     */
    public static function setDevCenterApiUrl($devcenterApiUrl = null)
    {
        // if DevCenter API URL endpoint already set, don't overwrite it with
        // empty or null
        if (self::$devcenterApiUrl && !$devcenterApiUrl) {
            return;
        }

        if ($devcenterApiUrl === null) {
            self::$devcenterApiUrl = self::DEVCENTER_API_URL_LIVE;
            } else if (is_string($devcenterApiUrl)) {
            self::$devcenterApiUrl = rtrim($devcenterApiUrl, '/');
        } else {
            self::$devcenterApiUrl = '';
        }
    }

    /**
     * @return string
     */
    public function getBaseImageUrl()
    {
        return $this->baseImageUrl;
    }

    /**
     * @return Query
     */
    public function getQuery()
    {
        $query = new Query($this->client, $this->getResultFactory());

        return $query;
    }

    /**
     * Returns the result of an auto completion API request.
     * Auto completion searches for products and categories by
     * a given prefix ($searchword).
     *
     * @param string $searchword The prefix search word to search for.
     * @param int    $limit      Maximum number of results.
     * @param array  $types      Array of types to search for (Constants::TYPE_...).
     * @param array  $cateogries List of category ids to be included
     *
     * @return Autocomplete
     */
    public function fetchAutocomplete(
        $searchword,
        $limit = 50,
        $types = [
            Autocomplete::TYPE_PRODUCTS,
            Autocomplete::TYPE_CATEGORIES
        ],
        array $cateogries = []
    ) {
        $query = $this->getQuery()
            ->fetchAutocomplete($searchword, $limit, $types, $cateogries)
        ;

        return $query->executeSingle();
    }

    /**
     * Returns the result of an spell correction API request.
     * Spell correction searches for products by
     * a given prefix ($searchword) and filter by categories ($categoryFilter).
     *
     * @param string $searchword The search word to search for.
     * @param integer[] $categoryIds Array of category Ids for filtering
     *
     * @return array
     */

    public function fetchSpellCorrection($searchword, $categoryIds = null)
    {
        if ($categoryIds !== null) {
            // we allow to pass a single ID instead of an array
            settype($categoryIds, 'array');

            foreach ($categoryIds as $categoryId) {
                if (!is_long($categoryId) && !ctype_digit($categoryId)) {
                    throw new \InvalidArgumentException('A single category ID must be an integer or a numeric string');
                } else if ($categoryId < 1) {
                    throw new \InvalidArgumentException('A single category ID must be greater than 0');
                }
            }
        }

        $query = $this->getQuery()
            ->fetchSpellCorrection($searchword, $categoryIds)
        ;

        return  $query->executeSingle();
    }

    /**
     * Fetch the basket of the given sessionId.
     *
     * @param string     $sessionId     Free to choose ID of the current website visitor.
     * @param array|null $productFields Product fields to fetch or null for default fields.
     * @param bool       $cleanErrors   Return all errors and then removes them from any further responses
     * @param bool       $refresh       Updates all products and variants
     *
     * @return Basket
     */
    public function fetchBasket($sessionId, array $productFields = null, $cleanErrors = true, $refresh = true)
    {
        $query = $this->getQuery()->fetchBasket($sessionId, $productFields, $cleanErrors, $refresh);

        return $query->executeSingle();
    }

    /**
     * Fetch the basket of the given sessionId.
     *
     * @param string     $sessionId     Free to choose ID of the current website visitor.
     * @param array|null $productFields Product fields to fetch or null for default fields.
     *
     * @return \AboutYou\SDK\Model\WishList
     *
     * @throws \AboutYou\SDK\Exception\MalformedJsonException
     * @throws \AboutYou\SDK\Exception\UnexpectedResultException
     */
    public function fetchWishList($sessionId, array $productFields = null)
    {
        $query = $this->getQuery()->fetchWishList($sessionId, $productFields);

        return $query->executeSingle();
    }

    /**
     * Adds a single item into the basket.
     * You can specify an amount. Please mind, that an amount > 1 will result in #amount basket positions.
     * So if you read out the basket again later, it's your job to merge the positions again.
     *
     * It is highly recommend to use the basket update method, to manage your items.
     *
     * @param string  $sessionId
     * @param integer $variantId
     * @param integer $amount
     * @param bool    $cleanErrors   Return all errors and then removes them from any further responses
     * @param bool    $refresh       Updates all products and variants
     *
     * @return Basket
     *
     * @throws \InvalidArgumentException
     */
    public function addItemToBasket($sessionId, $variantId, $amount = 1, $cleanErrors = true, $refresh = true)
    {
        if (!is_long($variantId)) {
            if (is_string($variantId) && ctype_digit($variantId)) {
                $variantId = intval($variantId);
            } else {
                throw new \InvalidArgumentException('the variant id must be an integer or string with digits');
            }
        }

        $basket = new Basket();

        for ($i=0; $i < $amount; $i++) {
            $item = new Basket\BasketItem(null, $variantId);

            $basket->updateItem($item);
        }

        return $this->updateBasket($sessionId, $basket, $cleanErrors, $refresh);
    }

    /**
     * Adds a single item into the wishlist.
     * You can specify an amount. Please mind, that an amount > 1 will result in #amount basket positions.
     * So if you read out the basket again later, it's your job to merge the positions again.
     *
     * It is highly recommend to use the basket update method, to manage your items.
     *
     * @param string $sessionId
     * @param integer $variantId
     * @param integer $amount
     *
     * @return Basket
     *
     * @throws \InvalidArgumentException
     */
    public function addItemToWishList($sessionId, $variantId, $amount = 1)
    {
        if (!is_long($variantId)) {
            if (is_string($variantId) && ctype_digit($variantId)) {
                $variantId = intval($variantId);
            } else {
                throw new \InvalidArgumentException('the variant id must be an integer or string with digits');
            }
        }

        $wishList = new WishList();

        for ($i=0; $i < $amount; $i++) {
            $item = new WishList\WishListItem(null, $variantId);

            $wishList->updateItem($item);
        }

        return $this->updateWishList($sessionId, $wishList);
    }

    /**
     * Remove basket item.
     *
     * @param string $sessionId     Free to choose ID of the current website visitor.
     * @param string[] $itemIds     array of basket item ids to delete, this can be sets or single items
     * @param bool   $cleanErrors
     * @param bool   $refresh
     *
     * @return \AboutYou\SDK\Model\Basket
     */
    public function removeItemsFromBasket($sessionId, $itemIds, $cleanErrors = true, $refresh = true)
    {
        $basket = new Basket();
        $basket->deleteItems($itemIds);

        return $this->updateBasket($sessionId, $basket, $cleanErrors, $refresh);
    }

    /**
     * @param string $sessionId
     * @param Basket $basket
     * @param bool   $cleanErrors
     * @param bool   $refresh
     *
     * @return \AboutYou\SDK\Model\Basket
     */
    public function updateBasket($sessionId, Basket $basket, $cleanErrors = true, $refresh = true)
    {
        $query = $this->getQuery()
            ->updateBasket($sessionId, $basket, $cleanErrors, $refresh)
        ;

        return $query->executeSingle();
    }

    /**
     * @param string $sessionId
     * @param WishList $wishList
     *
     * @return \AboutYou\SDK\Model\WishList
     */
    public function updateWishList($sessionId, WishList $wishList)
    {
        $query = $this->getQuery()
            ->updateWishList($sessionId, $wishList)
        ;

        return $query->executeSingle();
    }


    /**
     * Returns the result of a category search API request.
     * By passing one or several category ids it will return
     * a result of the categories data.
     *
     * @param mixed $ids either a single category ID as integer or an array of IDs
     *
     * @return \AboutYou\SDK\Model\CategoriesResult
     *
     * @deprecated use the CategoryManager instead of
     * @see getCategoryManager()
     */
    public function fetchCategoriesByIds($ids = null)
    {
        // we allow to pass a single ID instead of an array
        if ($ids !== null && !is_array($ids)) {
            $ids = [$ids];
        }

        foreach ($ids as $id) {
            if (!is_long($id) && !ctype_digit($id)) {
                throw new \InvalidArgumentException('A single category ID must be an integer or a numeric string');
            } else if ($id < 1) {
                throw new \InvalidArgumentException('A single category ID must be greater than 0');
            }
        }

        $categoryManager =  $this->getCategoryManager();

        return new CategoriesResult($categoryManager, $ids);
    }

    /**
     * @return CategoryTree
     *
     * @throws \AboutYou\SDK\Exception\MalformedJsonException
     * @throws \AboutYou\SDK\Exception\UnexpectedResultException
     *
     * @deprecated use the CategoryManager instead of
     * @see getCategoryManager()
     */
    public function fetchCategoryTree()
    {
        $categoryManager =  $this->getCategoryManager();

        return new CategoryTree($categoryManager);
    }

    /**
     * The Categories will be fetch automatically, if required by any other fetch method
     *
     * For Example
     * $productsResult = $api->fetchProductsByIds([12345], ['categories']);
     *  will fetch the categories with the same request.
     * $rootCategories = $api->getCategoryManager()->getCategoryTree();
     *  will give you all categories for your Menu without an other request
     *
     * @param bool $fetchIfEmpty
     *
     * @return CategoryManagerInterface
     */
    public function getCategoryManager($fetchIfEmpty = true)
    {
        $categoryManager = $this->getResultFactory()->getCategoryManager();

        if ($fetchIfEmpty && $categoryManager->isEmpty()) {
            $query = $this->getQuery()
                ->requireCategoryTree()
                ->executeSingle()
            ;
        }

        return $categoryManager;
    }

    /**
     * @param integer[] $ids
     * @param string[] $fields
     * @param boolean $loadStyles
     *
     * @return ProductsResult
     *
     * @throws \AboutYou\SDK\Exception\MalformedJsonException
     * @throws \AboutYou\SDK\Exception\UnexpectedResultException
     */
    public function fetchProductsByIds(
        array $ids,
        array $fields = [],
        $loadStyles = true
    ) {
        $query = $this->getQuery()
            ->fetchProductsByIds($ids, $fields, $loadStyles)
        ;

        $result = $query->executeSingle();

        $productsNotFound = $result->getProductsNotFound();
        if (!empty($productsNotFound) && $this->logger) {
            $this->logger->warning('products not found: appid=' . $this->appId . ' product ids=[' . join(',', $productsNotFound) . ']');
        }

        return $result;
    }

    /**
     * @param string[] $keys
     * @param string[] $fields
     * @param bool     $loadStyles
     *
     * @return ProductsResult
     */
    public function fetchProductsByStyleKeys(
        array $keys,
        array $fields = [],
        $loadStyles = true
    ) {
        $query = $this->getQuery()
            ->fetchProductsByStyleKeys($keys, $fields, $loadStyles)
        ;

        /** @var StylesResult $result */
        $result = $query->executeSingle();

        $stylesNotFound = $result->getStylesNotFound();
        if (!empty($stylesNotFound) && $this->logger) {
            $this->logger->warning(
                'Styles not found: appid=' . $this->appId . ' style keys=[' . join(',', $stylesNotFound) . ']'
            );
        }

        return $result;
    }

    /**
     * @param integer[] $ids
     * @param bool      $includeInactive
     * @param bool      $searchInactive
     *
     * @return \AboutYou\SDK\Model\VariantsResult
     *
     * @throws \AboutYou\SDK\Exception\MalformedJsonException
     * @throws \AboutYou\SDK\Exception\UnexpectedResultException
     */
    public function fetchVariantsByIds(
        array $ids,
        $includeInactive = true,
        $searchInactive = false
    ) {
        $query = $this->getQuery()
            ->fetchLiveVariantByIds($ids, $includeInactive, $searchInactive)
        ;

        $result = $query->executeSingle();

        $variantsNotFound = $result->getVariantsNotFound();
        if ($result->hasVariantsNotFound() && $this->logger) {
            $this->logger->warning('variants or products for variants not found: appid=' . $this->appId . ' variant ids=[' . join(',', $variantsNotFound) . ']');
        }

        return $result;
    }

    /**
     * @param string[] $eans
     * @param string[] $fields
     *
     * @return ProductsEansResult
     *
     * @throws \AboutYou\SDK\Exception\MalformedJsonException
     * @throws \AboutYou\SDK\Exception\UnexpectedResultException
     */
    public function fetchProductsByEans(
        array $eans,
        array $fields = []
    ) {
        $query = $this->getQuery()
            ->fetchProductsByEans($eans, $fields)
        ;

        return $query->executeSingle();
    }

    /**
     * @param ModelFactoryInterface $modelFactory
     */
    public function setResultFactory(ModelFactoryInterface $modelFactory)
    {
        $this->modelFactory = $modelFactory;
        $modelFactory->setBaseImageUrl($this->baseImageUrl);
    }

    /**
     * @return ModelFactoryInterface|DefaultModelFactory
     */
    public function getResultFactory()
    {
        if ($this->modelFactory === null) {
            $this->initDefaultFactory();
        } else if ($this->modelFactory instanceof \Closure) {
            $closure = $this->modelFactory;
            $closure($this);
        }

        return $this->modelFactory;
    }

    /**
     * @param ProductSearchCriteria $criteria
     *
     * @return ProductSearchResult
     *
     * @throws \AboutYou\SDK\Exception\MalformedJsonException
     * @throws \AboutYou\SDK\Exception\UnexpectedResultException
     */
    public function fetchProductSearch(
        ProductSearchCriteria $criteria
    ) {
        $query = $this->getQuery()
            ->fetchProductSearch($criteria)
        ;

        return $query->executeSingle();
    }

    /**
     * Fetch the facets of the given groupIds.
     *
     * @param array $groupIds The group ids.
     *
     * @return \AboutYou\SDK\Model\Facet[] With facet id as key.
     *
     * @throws \AboutYou\SDK\Exception\MalformedJsonException
     * @throws \AboutYou\SDK\Exception\UnexpectedResultException
     */
    public function fetchFacets(array $groupIds)
    {
        $query = $this->getQuery()
            ->fetchFacets($groupIds)
        ;

        return $query->executeSingle();
    }

    public function fetchFacetTypes()
    {
        $query = $this->getQuery()
            ->fetchFacetTypes()
        ;

        return $query->executeSingle();
    }

    /**
     * @param string|integer $orderId the order id
     *
     * @return \AboutYou\SDK\Model\Order the order
     */
    public function fetchOrder($orderId)
    {
        $query = $this->getQuery()
            ->fetchOrder($orderId)
        ;

        return $query->executeSingle();
    }

    /**
     * @param string $sessionId the session id
     * @param string $successUrl callback URL if the order was OK
     * @param string $cancelUrl callback URL if the order was canceled [optional]
     * @param string $errorUrl callback URL if the order had any exceptions [optional]
     *
     * @return \AboutYou\SDK\Model\InitiateOrder
     */
    public function initiateOrder(
        $sessionId,
        $successUrl,
        $cancelUrl = NULL,
        $errorUrl = NULL
    ) {
        $query = $this->getQuery()
            ->initiateOrder($sessionId, $successUrl, $cancelUrl, $errorUrl)
        ;

        return $query->executeSingle();
    }

    /**
     * Fetch single facets by id and group id
     * For example:
     * $ay->fetchFacet([
     *   ["id" => 123, "group_id" => 0 ],
     *   ["id" => 456, "group_id" => 0 ]
     * ]);
     *
     * @param array $params Array of (id, group_id) pairs
     *
     * @return \AboutYou\SDK\Model\Facet[] With facet id as key.
     *
     * @throws \AboutYou\SDK\Exception\MalformedJsonException
     * @throws \AboutYou\SDK\Exception\UnexpectedResultException
     */
    public function fetchFacet(array $params)
    {
        $query = $this->getQuery()
            ->fetchFacet($params)
        ;

        return $query->executeSingle();
    }

    /**
     * Returns the result of a suggest API request.
     * Suggestions are words that are often searched together
     * with the searchword you pass (e.g. "stretch" for "jeans").
     *
     * @param string $searchword The search string to search for.
     *
     * @return array
     */
    public function fetchSuggest($searchword)
    {
        $query = $this->getQuery()
            ->fetchSuggest($searchword)
        ;

        return $query->executeSingle();
    }

    /**
     * Returns the list of child apps
     *
     * @return array
     */
    public function fetchChildApps()
    {
        $query = $this->getQuery()
            ->fetchChildApps()
        ;

        return $query->executeSingle();
    }

    /**
     * @return string
     */
    public function getSessionId()
    {
        return session_id();
    }

    /**
     * @param string|null $sessionId
     *
     * @return ProductSearchCriteria
     */
    public function getProductSearchCriteria($sessionId = null)
    {
        if (!$sessionId) {
            $sessionId = $this->getSessionId();
        }

        return new ProductSearchCriteria($sessionId);
    }

    /**
     * Fetch and cache facets and/or categories.
     *
     * This can be used in an hourly cron job
     *
     * @param int $preCacheOption PRE_CACHE_ALL, PRE_CACHE_FACETS or PRE_CACHE_CATEGORY
     * @param bool $force
     */
    public function preCache($preCacheOption = self::PRE_CACHE_ALL, $force = false)
    {
        if ($preCacheOption & self::PRE_CACHE_ALL === 0) {
            throw new \RuntimeException('You must select at least one pre cache option');
        }

        if (!$this->cache) {
            throw new \RuntimeException('You can not use this this method, without setting the cache with the constructor');
        }

        $query = $this->getQuery();

        if ($preCacheOption & self::PRE_CACHE_FACETS) {
            $query->requireFacets($force);
        }

        if ($preCacheOption & self::PRE_CACHE_CATEGORY) {
            $query->requireCategoryTree($force);
        }

        $query->execute();
    }

    /**
     * Returns the URL to the ABOUT YOU JavaScript file for helper functions
     * to add product variants into the basket of Mary & Paul or auto-resizing
     * the Iframe. This URL may be changed in future, so please use this method instead
     * of hardcoding the URL into your HTML template.
     *
     * @return string URL to the JavaScript file
     */
    public function getJavaScriptURL()
    {
        if ($this->environment === Constants::API_ENVIRONMENT_STAGE) {
            $url = '//devcenter-staging-www1.pub.collins.kg/appjs/'.$this->appId.'.js';
        } else {
            $url = '//developer.aboutyou.de/appjs/'.$this->appId.'.js';
        }

        return $url;
    }


    /**
     * Returns a HTML script tag that loads the ABOUT YOU JavaScript fie.
     *
     * @return string HTML script tag
     */
    public function getJavaScriptTag()
    {
        $tag = '<script type="text/javascript" src="' . self::getJavaScriptURL() . '"></script>';

        return $tag;
    }

    /**
     * @private
     * @return array
     */
    public function getFacetGroups()
    {
        return [
            0   => 'brand',
            1   => 'color',
            5   => 'length',
            206 => 'size_code',
            172 => 'size_run',
            211 => 'channel',
            247 => 'care_symbol',
            173 => 'clothing_unisex_int',
            204 => 'clothing_unisex_onesize',
            175 => 'clothing_womens_de',
            180 => 'clothing_womens_inch',
            194 => 'shoes_unisex_eur',
            189 => 'clothing_mens_inch',
            185 => 'clothing_womens_scotchsoda_81hours',
            187 => 'clothing_mens_de',
            178 => 'clothing_womens_uk',
            183 => 'clothing_womens_us',
            181 => 'clothing_womens_belts_cm',
            190 => 'clothing_mens_belts_cm',
            176 => 'clothing_womens_it',
            192 => 'clothing_mens_acc'
        ];
    }

    /**
     * @param \Aboutyou\Common\Cache\CacheMultiGet|\Doctrine\Common\Cache\CacheMultiGet $cache
     *
     * @return DefaultModelFactory
     */
    public function initDefaultFactory($cache = null)
    {
        $categoryManager = new DefaultCategoryManager($cache, $this->appId);
        $facetManager    = new DefaultFacetManager($cache, $this->appId);

        $resultFactory = new DefaultModelFactory(
            $this,
            $facetManager,
            $categoryManager
        );

        $resultFactory->setBaseImageUrl($this->baseImageUrl);

        $this->setResultFactory($resultFactory);
    }

    /*
     * AuthSdk integration
     * @experimental
     */

    /**
     * Initialize the Auth API
     *
     * The Auth SDK requires additional parameters
     *
     * @param string $appSecret                The App Secret can be found in the DevCenter
     * @param string $callbackUrl              The User will redirect to this URL, if the is logged in succesful. The Auth SDK will then request the access token
     * @param bool   $usePopupLayout           If want to open the login page not in a popup, set this to false
     */
    public function initAuthApi(
        $appSecret,
        $callbackUrl,
        $usePopupLayout = true
    ) {
        $this->authSdk = new AuthSDK([
            'clientId'     => $this->getAppId(),
            'clientToken'  => $this->appPassword,
            'clientSecret' => $appSecret,
            'redirectUri'  => $callbackUrl,
            'popup'        => $usePopupLayout
        ], new SessionStorage());

        return $this->handleOAuth2Request();
    }

    protected function handleOAuth2Request()
    {
        $parsed = $this->authSdk->parseRedirectResponse();
        if (isset($_GET['state'], $_GET['code']) || isset($_GET['logout'])) {
            $redirectUrl = $this->authSdk->getState('redirectUrl');

            return $this->redirectAfterOAuth2Request($redirectUrl);
        }
    }

    protected function redirectAfterOAuth2Request($redirectUrl)
    {
        return $redirectUrl;
    }

    /**
     * @return bool
     */
    public function isAuthApiInitialized()
    {
        return $this->authSdk !== null;
    }

    /**
     * @throws \RuntimeException
     */
    protected function checkAuthSdk()
    {
        if (!$this->isAuthApiInitialized()) {
            throw new \RuntimeException('The Auth API must be initialized, please call initAuthApi() first');
        }
    }

    /**
     * @return bool
     *
     * @throws \RuntimeException
     */
    public function isLoggedIn()
    {
        $this->checkAuthSdk();

        $authResult = $this->authSdk->getUser();

        return $authResult->hasErrors() === false;
    }

    /**
     * Returns a json object, if logged in or null, if not
     *
     * @return \stdClass|null
     *
     * @throws \RuntimeException
     */
    public function getUserData()
    {
        $this->checkAuthSdk();

        $authResult = $this->authSdk->getUser();
        if ($authResult->hasErrors()) {
            return null;
        }
        $result = $authResult->getResult();
        $user = isset($result->response) ? json_decode($result->response) : false;

        return $user ? $user : null;
    }

    /**
     * @param string $redirectUrl
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    public function getLoginUrl($redirectUrl = null)
    {
        $this->checkAuthSdk();
        if (!empty($redirectUrl)) {
            $this->authSdk->setState('redirectUrl', $redirectUrl);
        }

        return $this->authSdk->getLoginUrl();
    }

    /**
     * @param string $redirectUrl
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    public function getLogoutUrl($redirectUrl = null)
    {
        $this->checkAuthSdk();

        return $this->authSdk->getLogoutUrl($redirectUrl);
    }

    protected static function checkIP($ip)
    {
        $regex4 = '/^(?:[0-9]{1,3}\.){3}[0-9]{1,3}$/';
        $regex6 = '/(([0-9a-fA-F]{1,4}:){7,7}[0-9a-fA-F]{1,4}|([0-9a-fA-F]{1,4}:){1,7}:|([0-9a-fA-F]{1,4}:){1,6}:[0-9a-fA-F]{1,4}|([0-9a-fA-F]{1,4}:){1,5}(:[0-9a-fA-F]{1,4}){1,2}|([0-9a-fA-F]{1,4}:){1,4}(:[0-9a-fA-F]{1,4}){1,3}|([0-9a-fA-F]{1,4}:){1,3}(:[0-9a-fA-F]{1,4}){1,4}|([0-9a-fA-F]{1,4}:){1,2}(:[0-9a-fA-F]{1,4}){1,5}|[0-9a-fA-F]{1,4}:((:[0-9a-fA-F]{1,4}){1,6})|:((:[0-9a-fA-F]{1,4}){1,7}|:)|fe80:(:[0-9a-fA-F]{0,4}){0,4}%[0-9a-zA-Z]{1,}|::(ffff(:0{1,4}){0,1}:){0,1}((25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9]).){3,3}(25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])|([0-9a-fA-F]{1,4}:){1,4}:((25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9]).){3,3}(25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9]))/';

        $valid = preg_match($regex4, $ip) > 0 || preg_match($regex6, $ip) > 0;

        if (!$valid) {
            throw new SDK\Exception\ApiErrorException(
                'invalid IP address passed'
            );
        }
    }

    /**
     *
     * @param string $ip IPv4 IP address. IPv6 is not supported yet. If null,
     * the IP from $_SERVER['REMOTE_ADDR'] will be used
     * @param string $devcenterApiUrl endpoint URL for the DevCenter API
     */
    public static function getCountryByIP($ip = null, $devcenterApiUrl = null)
    {
        self::setDevCenterApiUrl($devcenterApiUrl);

        if (!$ip) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        self::checkIP($ip);

        $url = self::$devcenterApiUrl.'/country/ip/'.$ip;
        $client = new \Guzzle\Http\Client($url);
        $request = $client->get();
        $response = $request->send();

        try {
            if (!$response->isSuccessful()) {
                throw new ApiErrorException(
                    $response->getReasonPhrase(),
                    $response->getStatusCode()
                );
            }
            try {
                if (!is_array($response->json())) {
                    throw new MalformedJsonException(
                        'result is not array'
                    );
                }
            } catch (\Exception $e) {
                throw new MalformedJsonException(
                    'unknown error occurred', 0, $e
                );
            }
        } catch (\Exception $e) {
            throw new ApiErrorException(
                'unknown error occurred', 0, $e
            );
        }

        return (object) $response->json();
    }

    /**
     * @param $apiEndPoint
     */
    public function setBaseImageUrlByApiEndPoint($apiEndPoint)
    {
        switch ($apiEndPoint) {
            case Constants::API_ENVIRONMENT_STAGE:
                $this->setBaseImageUrl(self::IMAGE_URL_STAGE);
                $this->environment = Constants::API_ENVIRONMENT_STAGE;
                break;
            case Constants::API_ENVIRONMENT_SANDBOX:
                $this->setBaseImageUrl(self::IMAGE_URL_SANDBOX);
                $this->environment = Constants::API_ENVIRONMENT_SANDBOX;
                break;
            default:
                $this->setBaseImageUrl(self::IMAGE_URL_LIVE);
                $this->environment = Constants::API_ENVIRONMENT_LIVE;
                break;
        }
    }
}
