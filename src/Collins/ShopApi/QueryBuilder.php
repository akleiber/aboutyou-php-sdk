<?php
/**
 * @auther nils.droege@antevorte.org
 * (c) Antevorte GmbH & Co KG
 */

namespace Collins\ShopApi;


use Collins\ShopApi\Exception\InvalidParameterException;

class QueryBuilder
{
    protected $query;

    public function __construct()
    {
        $this->query = [];
    }

    /**
     * Returns the result of an autocompletion API request.
     * Autocompletion searches for products and categories by
     * a given prefix ($searchword).
     *
     * @param string $searchword The prefix search word to search for.
     * @param int    $limit      Maximum number of results.
     * @param array  $types      Array of types to search for (Constants::TYPE_...).
     *
     * @return $this
     */
    public function fetchAutocomplete(
        $searchword,
        $limit = 50,
        $types = array(
            Constants::TYPE_PRODUCTS,
            Constants::TYPE_CATEGORIES
        )
    ) {
        $this->query[] = [
            'autocompletion' => array(
                'searchword' => $searchword,
                'types' => $types,
                'limit' => $limit
            )
        ];

        return $this;
    }

    /**
     * Fetch the basket of the given sessionId.
     *
     * @param string $sessionId Free to choose ID of the current website visitor.
     *
     * @return $this
     */
    public function fetchBasket($sessionId)
    {
        $this->query[] = [
            'basket_get' => [
                'session_id' => $sessionId
            ]
        ];

        return $this;
    }

    /**
     * Add product variant to basket.
     *
     * @param string $sessionId        Free to choose ID of the current website visitor.
     * @param int    $productVariantId ID of product variant.
     * @param int    $amount           Amount of items to add.
     *
     * @return $this
     */
    public function addToBasket($sessionId, $productVariantId, $amount = 1)
    {
        $this->query[] = [
            'basket_add' => array(
                'session_id' => $sessionId,
                'product_variant' => array(
                    array(
                        'id' => (int)$productVariantId,
                        'command' => 'add',
                        'amount' => (int)$amount,
                    ),
                ),
            )
        ];

        return $this;
    }

    /**
     * Remove product variant from basket.
     *
     * @param string $sessionId        Free to choose ID of the current website visitor.
     * @param int    $productVariantId ID of product variant.
     *
     * @return $this
     */
    public function removeFromBasket($sessionId, $productVariantId)
    {
        $this->query[] = [
            'basket_add' => array(
                'session_id' => $sessionId,
                'product_variant' => array(
                    array(
                        'id' => (int)$productVariantId,
                        'command' => 'set',
                        'amount' => 0,
                    ),
                ),
            )
        ];

        return $this;
    }

    /**
     * Update amount product variant in basket.
     *
     * @param string $sessionId        Free to choose ID of the current website visitor.
     * @param int    $productVariantId ID of product variant.
     * @param int    $amount           Amount to set.
     *
     * @return $this
     */
    public function updateBasketAmount($sessionId, $productVariantId, $amount)
    {
        $this->query[] = [
            'basket_add' => array(
                'session_id' => $sessionId,
                'product_variant' => array(
                    array(
                        'id' => (int)$productVariantId,
                        'command' => 'set',
                        'amount' => (int)$amount,
                    ),
                ),
            )
        ];

        return $this;
    }

    /**
     * Returns the result of a category search API request.
     * By passing one or several category ids it will return
     * a result of the categories data.
     *
     * @param mixed $ids either a single category ID as integer or an array of IDs
     *
     * @return $this
     */
    public function fetchCategoriesByIds($ids)
    {
        // we allow to pass a single ID instead of an array
        if (!is_array($ids)) {
            $ids = array($ids);
        }

        $this->query[] = [
            'category' => array(
                'ids' => $ids
            )
        ];

        return $this;
    }

    /**
     * @param int $maxDepth  -1 <= $maxDepth <= 10
     *
     * @return $this
     */
    public function fetchCategoryTree($maxDepth = -1)
    {
        if ($maxDepth >= 0) {
            $params = ['max_depth' => $maxDepth];
        } else {
            $params = new \stdClass();
        }
        $this->query[] = [
            'category_tree' => $params,
        ];

        return $this;
    }

    /**
     * @param array $ids
     * @param array $fields
     *
     * @return $this
     */
    public function fetchProductsByIds(
        array $ids,
        array $fields = []
    ) {
        // we allow to pass a single ID instead of an array
        settype($ids, 'array');

        $this->query[] = [
            'products' => array(
                'ids' => $ids,
                'fields' => $fields
            )
        ];

        return $this;
    }

    /**
     * @param string $userSessionId
     * @param array|CriteriaInterface $filter
     * @param array $result
     *
     * @return $this
     */
    public function fetchProductSearch(
        $userSessionId,
        $filter = array(),
        array $result = array(
            'fields' => []
        )
    ) {
        $data = array(
            'product_search' => array(
                'session_id' => (string)$userSessionId
            )
        );

        if ($filter instanceof CriteriaInterface) {
            $filter = $filter->toArray();
        }
        if (count($filter) > 0) {
            $data['product_search']['filter'] = $filter;
        }

        if (count($result) > 0) {
            $data['product_search']['result'] = $result;
        }

        $this->query[] = $data;

        return $this;
    }

    /**
     * Fetch the facets of the given groupIds.
     *
     * @param array $groupIds The group ids.
     *
     * @return \Collins\ShopApi\Model\Facet[] With facet id as key.
     */
    public function fetchFacets(array $groupIds)
    {
        if (empty($groupIds)) {
            throw new InvalidParameterException('no groupId given');
        }

        $this->query[] = [
            'facets' => array(
                'group_ids' => $groupIds
            )
        ];

        return $this;
    }

    /**
     * Returns the result of a suggest API request.
     * Suggestions are words that are often searched together
     * with the searchword you pass (e.g. "stretch" for "jeans").
     *
     * @param string $searchword The search string to search for.
     *
     * @return $this
     */
    public function fetchSuggest($searchword)
    {
        $this->query[] = [
            'suggest' => array(
                'searchword' => $searchword
            )
        ];

        return $this;
    }

    public function getQueryString()
    {
        return json_encode($this->query);
    }

}