<?php namespace Nano7\Database\Model;

use Nano7\Database\Query\Builder as QueryBuilder;

trait HasScopes
{
    /**
     * @var array
     */
    protected $scopes = [];

    /**
     * Register a new global scope on the model.
     *
     * @param  Scope $scope
     * @return bool
     *
     * @throws \InvalidArgumentException
     */
    public function registerScope($scope)
    {
        if ($scope instanceof Scope) {
            $this->scopes[get_class($scope)] = $scope;

            return true;
        }

        throw new \InvalidArgumentException('Global scope must be an instance of Scope.');
    }

    /**
     * Apply scopes in query.
     *
     * @param QueryBuilder $query
     * @param Model $model
     * @return $this
     */
    protected function applyScopes(QueryBuilder $query, Model $model)
    {
        foreach ($this->scopes as $scope) {
            $scope->apply($query, $model);
        }

        return $this;
    }
}