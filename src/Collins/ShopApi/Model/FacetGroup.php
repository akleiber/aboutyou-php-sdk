<?php
/**
 * @auther nils.droege@antevorte.org
 * (c) Antevorte GmbH & Co KG
 */

namespace Collins\ShopApi\Model;


class FacetGroup implements FacetUniqueKeyInterface, FacetGetGroupInterface
{
    /** @var Facet[] */
    protected $facets;

    /** @var integer */
    protected $id;

    /** @var string */
    protected $name;

    /**
     * @param integer $id
     * @param string  $name
     */
    public function __construct($id, $name)
    {
        $this->id = $id;
        $this->name = $name;
        $this->facets = array();
    }

    /**
     * @param Facet $facet
     */
    public function addFacet(Facet $facet)
    {
        $this->facets[$facet->getId()] = $facet;
    }

    /**
     * @param Facet[] $facet
     */
    public function addFacets(array $facets)
    {
        foreach ($facets as $facet) {
            $this->addFacet($facet);
        }
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return integer
     */
    public function getGroupId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return Facet[]
     */
    public function getFacets()
    {
        return $this->facets;
    }

    /**
     * facet groups are equal, if the ids and all child ids are equal
     *
     * @param FacetGroup $facetGroup
     *
     * @return boolean
     */
    public function isEqual(FacetGroup $facetGroup)
    {
        if ($this->id !== $facetGroup->id) return false;

        return $this->getUniqueKey() === $facetGroup->getUniqueKey();
    }

    /**
     * @see isEqual
     *
     * @return string
     */
    public function getUniqueKey()
    {
        $facetIds = array_keys($this->facets);
        sort($facetIds);

        return $this->id . ':' . join(',', $facetIds);
    }

    public function getIds()
    {
        return array(
            $this->id => array_keys($this->facets)
        );
    }


    public function contains(Facet $facet)
    {
        return isset($this->facets[$facet->getId()]);
    }
} 