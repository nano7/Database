<?php namespace Nano7\Database\Model;

use Nano7\Database\Query\Builder as QueryBuilder;

class Builder
{
    /**
     * @var Model
     */
    protected $model;

    /**
     * @var QueryBuilder
     */
    protected $query;

    /**
     * The methods that should be returned from query builder.
     *
     * @var array
     */
    protected $passthru = [
        'insert', 'insertGetId',
        'exists', 'doesntExist', 'count', 'min', 'max', 'avg', 'sum',
    ];

    /**
     * Execute the query as a "select" statement.
     *
     * @param  array  $columns
     * @return \Illuminate\Support\Collection
     */
    public function get($columns = ['*'])
    {
        $models = $this->getModels($columns);

        return collect($models);
    }

    /**
     * Execute the query and get the first result.
     *
     * @param  array  $columns
     * @return Model|null
     */
    public function first($columns = ['*'])
    {
        $this->query->limit(1);

        return $this->get($columns)->first();
    }

    /**
     * Execute a query for a single record by ID.
     *
     * @param  int    $id
     * @param  array  $columns
     * @return Model|null
     */
    public function find($id, $columns = ['*'])
    {
        $this->query->where('_id', '=', $id);

        return $this->first($columns);
    }

    /**
     * Update documents.
     *
     * @param array $values
     * @return int
     */
    public function update(array $values)
    {
        $count = 0;

        foreach ($this->getModels() as $model) {
            $model->fill($values);
            $count += $model->save() ? 1 : 0;
        }

        return 0;
    }

    /**
     * Delete documents.
     *
     * @return int
     */
    public function delete()
    {
        $count = 0;

        foreach ($this->getModels() as $model) {
            $count += $model->delete() ? 1 : 0;
        }

        return 0;
    }

    /**
     * Get the hydrated models without eager loading.
     *
     * @param  array  $columns
     * @return Model[]
     */
    public function getModels($columns = ['*'])
    {
        $models = [];

        $documents = $this->query->get($columns);
        foreach ($documents as $doc) {
            $models[] = $this->model->newInstance($doc, true);
        }

        return $models;
    }

    /**
     * @param $model
     */
    public function setModel($model)
    {
        $this->model = $model;
    }

    /**
     * @return Model
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * @param QueryBuilder
     */
    public function setQuery($query)
    {
        $this->query = $query;
    }

    /**
     * @return QueryBuilder
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @param $name
     * @param $arguments
     * @return $this|mixed
     */
    public function __call($name, $arguments)
    {
        // Executar do model
        if ((! is_null($this->model)) && method_exists($this->model, $name)) {
            return call_user_func_array([$this->model, $name], $arguments);
        }

        // Executar da query
        if ((! is_null($this->query)) && method_exists($this->query, $name)) {
            $return = call_user_func_array([$this->query, $name], $arguments);

            // Verificar se deve encaminhar o retorno da query
            if (in_array($name, $this->passthru)) {
                return $return;
            }
        }

        return $this;
    }
}