<?php namespace Nano7\Database;

use Nano7\Database\Query\Builder;

interface ConnectionInterface
{
    /**
     * Get collection by name.
     *
     * @param $name
     * @return Builder
     */
    public function collection($name);

    /**
     * Create newq collection.
     *
     * @param $name
     * @param array $options
     */
    public function createCollection($name, $options = []);

    /**
     * Drop a collection.
     *
     * @param $name
     * @param array $options
     */
    public function dropCollection($name, $options = []);

    /**
     * Create new index.
     *
     * @param $collection
     * @param $columns
     * @param array $options
     * @return string
     */
    public function createIndex($collection, $columns, array $options = []);

    /**
     * Drop a index.
     *
     * @param $collection
     * @param $indexName
     * @param array $options
     * @return array|object
     */
    public function dropIndex($collection, $indexName, array $options = []);

    /**
     * Run an insert statement against the database.
     *
     * @param  string  $collection
     * @param  array   $bindings
     * @return bool
     */
    public function insert($collection, $bindings = []);
}