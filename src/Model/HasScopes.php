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
            $this->scopes[$scope->getName()] = $scope;

            return true;
        }

        throw new \InvalidArgumentException('Global scope must be an instance of Scope.');
    }

    /**
     * Apply scopes in query.
     *
     * @param QueryBuilder $query
     * @param Model $model
     * @param array $ignoreScopes
     * @return $this
     */
    protected function applyScopes(QueryBuilder $query, Model $model, $ignoreScopes = [])
    {
        foreach ($this->scopes as $sid => $scope) {
            if (! in_array($sid, $ignoreScopes)) {
                $scope->apply($query, $model);
            }
        }

        return $this;
    }
}