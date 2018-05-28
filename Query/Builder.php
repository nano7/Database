<?php namespace Nano7\Database\Query;

use MongoDB\Collection;
use Nano7\Database\Connection;
use Nano7\Database\ConnectionInterface;

class Builder
{
    use Wheres;
    use Runner;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var Collection
     */
    protected $collection;

    /**
     * The column projections.
     *
     * @var array
     */
    public $projections;

    /**
     * The columns that should be returned.
     *
     * @var array
     */
    public $columns;

    /**
     * The table which the query is targeting.
     *
     * @var string
     */
    public $from;

    /**
     * The where constraints for the query.
     *
     * @var array
     */
    public $wheres = [];

    /**
     * The orderings for the query.
     *
     * @var array
     */
    public $orders;

    /**
     * The maximum number of records to return.
     *
     * @var int
     */
    public $limit;

    /**
     * The number of records to skip.
     *
     * @var int
     */
    public $offset;

    /**
     * Custom options to add to the query.
     *
     * @var array
     */
    public $options = [];

    /**
     * Indicates if the query returns distinct results.
     *
     * @var bool
     */
    public $distinct = false;

    /**
     * All of the available clause operators.
     *
     * @var array
     */
    public $operators = [
        '='        => '=',
        '<'        => '$lt',
        '>'        => '$gt',
        '<='       => '$lte',
        '>='       => '$gte',
        '<>'       => '$ne',
        '!='       => '$ne',
        'like'     => 'like',
        'not like' => 'not like',
        'exists'   => 'exists',
    ];

    /**
     * @param ConnectionInterface $connection
     */
    public function __construct(ConnectionInterface $connection, $from, Collection $collection)
    {
        $this->connection = $connection;
        $this->from       = $from;
        $this->collection = $collection;
    }

    /**
     * Set the projections.
     *
     * @param  array $columns
     * @return $this
     */
    public function project($columns)
    {
        $this->projections = is_array($columns) ? $columns : func_get_args();

        return $this;
    }

    /**
     * Set the columns to be selected.
     *
     * @param  array|mixed  $columns
     * @return $this
     */
    public function select($columns = ['*'])
    {
        $this->columns = is_array($columns) ? $columns : func_get_args();

        return $this;
    }

    /**
     * Add an "order by" clause to the query.
     *
     * @param  string  $column
     * @param  string  $direction
     * @return $this
     */
    public function orderBy($column, $direction = 'asc')
    {
        $this->orders[] = [
            'column' => $column,
            'direction' => strtolower($direction) == 'asc' ? 'asc' : 'desc',
        ];

        return $this;
    }

    /**
     * Set the "offset" value of the query.
     *
     * @param  int  $value
     * @return $this
     */
    public function offset($value)
    {
        $this->offset = max(0, $value);

        return $this;
    }

    /**
     * Set the "limit" value of the query.
     *
     * @param  int  $value
     * @return $this
     */
    public function limit($value)
    {
        if ($value >= 0) {
            $this->limit = $value;
        }

        return $this;
    }

    /**
     * Set the limit and offset for a given page.
     *
     * @param  int  $page
     * @param  int  $perPage
     * @return $this
     */
    public function forPage($page, $perPage = 15)
    {
        return $this->offset(($page - 1) * $perPage)->limit($perPage);
    }

    /**
     * Set custom options for the query.
     *
     * @param  array $options
     * @return $this
     */
    public function options(array $options)
    {
        $this->options = $options;

        return $this;
    }

    /**
     * Force the query to only return distinct results.
     *
     * @param bool|string $column
     * @return $this
     */
    public function distinct($column = false)
    {
        $this->distinct = true;

        if ($column) {
            $this->columns = [$column];
        }

        return $this;
    }
}