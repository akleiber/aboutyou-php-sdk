<?php
/**
 * @author nils.droege@aboutyou.de
 * (c) ABOUT YOU GmbH
 */

namespace AboutYou\SDK\Model\ProductSearchResult;

class FacetCounts extends TermsCounts
{
    /** @var integer */
    protected $groupId;

    /** @var FacetCount[] */
    protected $facetCounts;

    /**
     * @param integer      $groupId
     * @param \stdClass    $jsonObject
     * @param FacetCount[] $facetCounts
     *
     * @return FacetCounts
     */
    public static function createFromJson($groupId, \stdClass $jsonObject, $facetCounts)
    {
        $self = new static($jsonObject->total, $jsonObject->other, $jsonObject->missing);
        $self->groupId = $groupId;

        $self->facetCounts = $facetCounts;

        return $self;
    }

    public function getGroupId()
    {
        return $this->groupId;
    }

    public function getFacetCounts()
    {
        return $this->facetCounts;
    }
}