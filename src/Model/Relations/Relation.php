<?php namespace Nano7\Database\Model\Relations;

abstract class Relation
{
    /**
     * @var mixed
     */
    protected $parent;

    /**
     * @var mixed
     */
    protected $related;

    /**
     * @param $parent
     * @param $related
     */
    public function __construct($parent, $related)
    {
        $this->parent = $parent;
        $this->related = $related;
    }

    /**
     * Get the parent model of the relation.
     *
     * @return mixed
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Get the related model of the relation.
     *
     * @return mixed
     */
    public function getRelated()
    {
        return $this->related;
    }

    /**
     * Get the results of the relationship.
     *
     * @return mixed
     */
    abstract public function getResults();
}