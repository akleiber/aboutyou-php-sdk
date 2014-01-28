<?php
namespace Collins\ShopApi\Results;

/**
 * Contains the result data of a facet API request.
 *
 * @author Antevorte GmbH
 */
class FacetResult extends BaseResult
{
    /**
     * Root key of the JSON API result
     * @var string
     */
    protected $resultKey = 'facets';

    public $facet = array();
    public $hits = null;

    /**
     * Returns the part of the result for the facet with the passed ID
     *
     * @param integer $groupId ID of the facet group
     * @param array|int $ids array of facet IDs or single ID
     *
     * @return array facet data or null if facet not found
     */
    public function getFacetByIds($groupId, $ids)
    {
        if (!is_array($ids)) {
            $ids = array($ids);
        }

        $facets = array();

        foreach ($this->facet as $facet) {
            if ($facet['id'] == $groupId &&
                in_array($facet['facet_id'], $ids)
            ) {
                $facets[] = $facet;
            }
        }

        return $facets;
    }
}