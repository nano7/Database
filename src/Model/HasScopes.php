<?php namespace Nano7\Database\Model;

use Nano7\Support\Arr;
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
            $class = get_called_class();
            static::$scopes[$class][$scope->getName()] = $scope;

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
        // Verificar se deve ignorar todos os scopes
        if (in_array('*', $ignoreScopes)) {
            return;
        }

        // Carregar lista de scopes
        $class = get_called_class();
        $scopes = Arr::get(static::$scopes, $class, []);

        // Aplicar scopes
        foreach ($scopes as $sid => $scope) {
            // Verificar se deve ignorar scope especifico
            if (! in_array($sid, $ignoreScopes)) {
                $scope->apply($query, $model);
            }
        }
    }
}