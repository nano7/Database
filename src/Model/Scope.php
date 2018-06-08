<?php namespace Nano7\Database\Model;

use Nano7\Database\Query\Builder as QueryBuilder;

abstract class Scope
{
    /**
     * Apply the scope to a given Model query builder.
     *
     * @param  QueryBuilder $query
     * @param  Model  $model
     * @return void
     */
    abstract public function apply(QueryBuilder $query, Model $model);
}