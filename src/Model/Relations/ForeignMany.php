<?php namespace Nano7\Database\Model\Relations;

use Nano7\Database\Model\Model;
use Nano7\Database\Model\Builder;
use Illuminate\Support\Collection;

class ForeignMany extends Relation
{
    /**
     * The Eloquent query builder instance.
     *
     * @var Builder
     */
    protected $query;

    /**
     * The foreign key of the parent model.
     *
     * @var string
     */
    protected $foreignKey;

    /**
     * The local key of the parent model.
     *
     * @var string
     */
    protected $localKey;

    /**
     * @param $parent
     * @param Builder $query
     */
    public function __construct($parent, Builder $query, $foreignKey, $localKey)
    {
        parent::__construct($parent, $query->getModel());

        $this->query = $query;
        $this->foreignKey = $foreignKey;
        $this->localKey = $localKey;

        $this->addConstraints();
    }

    /**
     * @return Builder
     */
    public function query()
    {
        return $this->query;
    }

    /**
     * Set the base constraints on the relation query.
     *
     * @return void
     */
    public function addConstraints()
    {
        $this->query->where($this->foreignKey, '=', $this->parent->{$this->localKey});
    }

    /**
     * Get the results of the relationship.
     *
     * @return Collection
     */
    public function getResults()
    {
        return $this->query->get();
    }

    /**
     * @param array $attributes
     * @return Model
     */
    public function make($attributes = [])
    {
        $item = $this->related->newInstance($attributes, false);
        $item->{$this->foreignKey} = $this->parent->{$this->localKey};

        return $item;
    }
}