<?php namespace Nano7\Database\Model;

use Nano7\Database\Query\Builder as QueryBuilder;

abstract class Scope
{
    /**
     * Id of scope.
     *
     * @var string
     */
    protected $name;

    /**
     * Apply the scope to a given Model query builder.
     *
     * @param  QueryBuilder $query
     * @param  Model  $model
     * @return void
     */
    abstract public function apply(QueryBuilder $query, Model $model);

    /**
     * Return id name of scope.
     *
     * @return string
     */
    public function getName()
    {
        if (is_null($this->name)) {
            return get_class($this);
        }

        return $this->name;
    }
}