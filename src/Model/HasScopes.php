<?php namespace Nano7\Database\Model;

use Nano7\Database\Query\Builder as QueryBuilder;

trait HasScopes
{
    /**
     * Register a new global scope on the model.
     *
     * @param  Scope $scope
     * @return bool
     *
     * @throws \InvalidArgumentException
     */
    public static function registerScope($scope)
    {
        if ($scope instanceof Scope) {
            static::$scopes[$scope->getName()] = $scope;

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
     * @return void
     */
    protected function applyScopes(QueryBuilder $query, Model $model, $ignoreScopes = [])
    {
        foreach (static::$scopes as $sid => $scope) {
            if (! in_array($sid, $ignoreScopes)) {
                $scope->apply($query, $model);
            }
        }
    }
}