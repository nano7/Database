<?php namespace Nano7\Database\Query;

use DateTime;
use MongoDB\Collection;
use Illuminate\Support\Str;
use MongoDB\BSON\ObjectID;
use MongoDB\BSON\Regex;
use MongoDB\BSON\UTCDateTime;

/**
 * Class Runner
 * @method Builder limit($value)
 * @method Builder where($column, $operator = null, $value = null, $boolean = 'and')
 * @property Collection $collection
 * @property array $projections
 * @property array $columns
 * @property string $from
 * @property array $wheres
 * @property array $orders
 * @property array $options
 * @property array $operators
 * @property int $limit
 * @property int $offset
 * @property bool $distinct
 */
trait Runner
{
    /**
     * Execute the query as a "select" statement.
     *
     * @param  array  $columns
     * @return \Illuminate\Support\Collection
     */
    public function get($columns = ['*'])
    {
        $results = $this->getFresh($columns);

        return collect($results);
    }

    /**
     * Execute the query and get the first result.
     *
     * @param  array  $columns
     * @return object|null
     */
    public function first($columns = ['*'])
    {
        return $this->limit(1)->get($columns)->first();
    }

    /**
     * Execute a query for a single record by ID.
     *
     * @param  int    $id
     * @param  array  $columns
     * @return mixed|static
     */
    public function find($id, $columns = ['*'])
    {
        return $this->where('_id', '=', $id)->first($columns);
    }

    /**
     * Insert documents.
     *
     * @param array $values
     * @return bool
     */
    public function insert(array $values)
    {
        // Since every insert gets treated like a batch insert, we will have to detect
        // if the user is inserting a single document or an array of documents.
        $batch = true;

        foreach ($values as $value) {
            // As soon as we find a value that is not an array we assume the user is
            // inserting a single document.
            if (!is_array($value)) {
                $batch = false;
                break;
            }
        }

        if (!$batch) {
            $values = [$values];
        }

        // Batch insert
        $result = $this->collection->insertMany($values);

        return (1 == (int) $result->isAcknowledged());
    }

    /**
     * Insert documet and return ID.
     *
     * @param array $values
     * @param null $sequence
     * @return mixed|null
     */
    public function insertGetId(array $values, $sequence = null)
    {
        $result = $this->collection->insertOne($values);

        if (1 == (int) $result->isAcknowledged()) {
            if (is_null($sequence)) {
                $sequence = '_id';
            }

            // Return id
            return $sequence == '_id' ? trim($result->getInsertedId()) : $values[$sequence];
        }

        return null;
    }

    /**
     * Update documents.
     *
     * @param array $values
     * @param array $options
     * @return int
     */
    public function update(array $values, array $options = [])
    {
        // Use $set as default operator.
        if (!Str::startsWith(key($values), '$')) {
            $values = ['$set' => $values];
        }

        // Update multiple items by default.
        if (!array_key_exists('multiple', $options)) {
            $options['multiple'] = true;
        }

        $wheres = $this->compileWheres();

        $result = $this->collection->updateMany($wheres, $values, $options);

        if (1 == (int) $result->isAcknowledged()) {
            return $result->getModifiedCount() ? $result->getModifiedCount() : $result->getUpsertedCount();
        }

        return 0;
    }

    /**
     * Delete documents.
     *
     * @param null $id
     * @return int
     */
    public function delete($id = null)
    {
        // If an ID is passed to the method, we will set the where clause to check
        // the ID to allow developers to simply and quickly remove a single row
        // from their database without manually specifying the where clauses.
        if (!is_null($id)) {
            $this->where('_id', '=', $id);
        }

        $wheres = $this->compileWheres();
        $result = $this->collection->deleteMany($wheres);

        if (1 == (int) $result->isAcknowledged()) {
            return $result->getDeletedCount();
        }

        return 0;
    }

    /**
     * Execute the query as a fresh "select" statement.
     *
     * @param  array $columns
     * @return array|static[]|Collection
     */
    protected function getFresh($columns = [])
    {
        // If no columns have been specified for the select statement, we will set them
        // here to either the passed columns, or the standard default of retrieving
        // all of the columns on the table using the "wildcard" column character.
        if (is_null($this->columns)) {
            $this->columns = $columns;
        }

        // Drop all columns if * is present, MongoDB does not work this way.
        if (in_array('*', $this->columns)) {
            $this->columns = [];
        }

        // Compile wheres
        $wheres = $this->compileWheres();

        // Use MongoDB's aggregation framework when using grouping or aggregation functions.
        //if ($this->groups || $this->aggregate) {
        //    return $this->getFreshGroupAndAggregate($wheres);
        //}

        // Return distinct results directly
        if ($this->distinct) {
            return $this->getFreshDistinct($wheres);
        }

        return $this->getFreshNormal($wheres);
    }

    /**
     * Fetch Distinct.
     *
     * @param $wheres
     * @return \mixed[]
     */
    protected function getFreshGroupAndAggregate($wheres)
    {
        /*
            $group = [];
            $unwinds = [];

            // Add grouping columns to the $group part of the aggregation pipeline.
            if ($this->groups) {
                foreach ($this->groups as $column) {
                    $group['_id'][$column] = '$' . $column;

                    // When grouping, also add the $last operator to each grouped field,
                    // this mimics MySQL's behaviour a bit.
                    $group[$column] = ['$last' => '$' . $column];
                }

                // Do the same for other columns that are selected.
                foreach ($this->columns as $column) {
                    $key = str_replace('.', '_', $column);

                    $group[$key] = ['$last' => '$' . $column];
                }
            }

            // Add aggregation functions to the $group part of the aggregation pipeline,
            // these may override previous aggregations.
            if ($this->aggregate) {
                $function = $this->aggregate['function'];

                foreach ($this->aggregate['columns'] as $column) {
                    // Add unwind if a subdocument array should be aggregated
                    // column: subarray.price => {$unwind: '$subarray'}
                    if (count($splitColumns = explode('.*.', $column)) == 2) {
                        $unwinds[] = $splitColumns[0];
                        $column = implode('.', $splitColumns);
                    }

                    // Translate count into sum.
                    if ($function == 'count') {
                        $group['aggregate'] = ['$sum' => 1];
                    } // Pass other functions directly.
                    else {
                        $group['aggregate'] = ['$' . $function => '$' . $column];
                    }
                }
            }

            // When using pagination, we limit the number of returned columns
            // by adding a projection.
            if ($this->paginating) {
                foreach ($this->columns as $column) {
                    $this->projections[$column] = 1;
                }
            }

            // The _id field is mandatory when using grouping.
            if ($group && empty($group['_id'])) {
                $group['_id'] = null;
            }

            // Build the aggregation pipeline.
            $pipeline = [];
            if ($wheres) {
                $pipeline[] = ['$match' => $wheres];
            }

            // apply unwinds for subdocument array aggregation
            foreach ($unwinds as $unwind) {
                $pipeline[] = ['$unwind' => '$' . $unwind];
            }

            if ($group) {
                $pipeline[] = ['$group' => $group];
            }

            // Apply order and limit
            if ($this->orders) {
                $pipeline[] = ['$sort' => $this->orders];
            }
            if ($this->offset) {
                $pipeline[] = ['$skip' => $this->offset];
            }
            if ($this->limit) {
                $pipeline[] = ['$limit' => $this->limit];
            }
            if ($this->projections) {
                $pipeline[] = ['$project' => $this->projections];
            }

            $options = [
                'typeMap' => ['root' => 'array', 'document' => 'array'],
            ];

            // Add custom query options
            if (count($this->options)) {
                $options = array_merge($options, $this->options);
            }

            // Execute aggregation
            $results = iterator_to_array($this->collection->aggregate($pipeline, $options));

            // Return results
            return $this->useCollections ? new Collection($results) : $results;
        /**/
    }

    /**
     * Fetch Distinct.
     *
     * @param $wheres
     * @return \mixed[]
     */
    protected function getFreshDistinct($wheres)
    {
        $column = isset($this->columns[0]) ? $this->columns[0] : '_id';

        // Execute distinct
        if ($wheres) {
            $result = $this->collection->distinct($column, $wheres);
        } else {
            $result = $this->collection->distinct($column);
        }

        return $result;
    }

    /**
     * Fetch Normal.
     *
     * @param $wheres
     * @return \mixed[]
     */
    protected function getFreshNormal($wheres)
    {
        $columns = [];

        // Convert select columns to simple projections.
        foreach ($this->columns as $column) {
            $columns[$column] = true;
        }

        // Add custom projections.
        if ($this->projections) {
            $columns = array_merge($columns, $this->projections);
        }
        $options = [];

        // Apply order, offset, limit and projection
        //if ($this->timeout) {
        //    $options['maxTimeMS'] = $this->timeout;
        //}
        if ($this->orders) {
            $options['sort'] = $this->orders;
        }
        if ($this->offset) {
            $options['skip'] = $this->offset;
        }
        if ($this->limit) {
            $options['limit'] = $this->limit;
        }
        if ($columns) {
            $options['projection'] = $columns;
        }
        // if ($this->hint)    $cursor->hint($this->hint);

        // Fix for legacy support, converts the results to arrays instead of objects.
        $options['typeMap'] = ['root' => 'array', 'document' => 'array'];

        // Add custom query options
        if (count($this->options)) {
            $options = array_merge($options, $this->options);
        }

        // Execute query and get MongoCursor
        $cursor = $this->collection->find($wheres, $options);

        // Return results as an array with numeric keys
        $results = iterator_to_array($cursor, false);

        return $results;
    }

    /**
     * Compile the where array.
     *
     * @return array
     */
    protected function compileWheres()
    {
        // The wheres to compile.
        $wheres = $this->wheres ?: [];

        // We will add all compiled wheres to this array.
        $compiled = [];

        foreach ($wheres as $i => &$where) {
            // Make sure the operator is in lowercase.
            if (isset($where['operator'])) {
                $where['operator'] = strtolower($where['operator']);

                if (array_key_exists($where['operator'], $this->operators)) {
                    $where['operator'] = $this->operators[$where['operator']];
                }
            }

            // Convert id's.
            if (isset($where['column']) && ($where['column'] == '_id' || Str::endsWith($where['column'], '._id'))) {
                // Multiple values.
                if (isset($where['values'])) {
                    foreach ($where['values'] as &$value) {
                        $value = $this->convertKey($value);
                    }
                } // Single value.
                elseif (isset($where['value'])) {
                    $where['value'] = $this->convertKey($where['value']);
                }
            }

            // Convert DateTime values to UTCDateTime.
            if (isset($where['value'])) {
                if (is_array($where['value'])) {
                    array_walk_recursive($where['value'], function (&$item, $key) {
                        if ($item instanceof DateTime) {
                            $item = new UTCDateTime($item->getTimestamp() * 1000);
                        }
                    });
                } else {
                    if ($where['value'] instanceof DateTime) {
                        $where['value'] = new UTCDateTime($where['value']->getTimestamp() * 1000);
                    }
                }
            } elseif (isset($where['values'])) {
                array_walk_recursive($where['values'], function (&$item, $key) {
                    if ($item instanceof DateTime) {
                        $item = new UTCDateTime($item->getTimestamp() * 1000);
                    }
                });
            }

            // The next item in a "chain" of wheres devices the boolean of the
            // first item. So if we see that there are multiple wheres, we will
            // use the operator of the next where.
            if ($i == 0 && count($wheres) > 1 && $where['boolean'] == 'and') {
                $where['boolean'] = $wheres[$i + 1]['boolean'];
            }

            // We use different methods to compile different wheres.
            $method = "compileWhere{$where['type']}";
            $result = $this->{$method}($where);

            // Wrap the where with an $or operator.
            if ($where['boolean'] == 'or') {
                $result = ['$or' => [$result]];
            }

            // If there are multiple wheres, we will wrap it with $and. This is needed
            // to make nested wheres work.
            elseif (count($wheres) > 1) {
                $result = ['$and' => [$result]];
            }

            // Merge the compiled where with the others.
            $compiled = array_merge_recursive($compiled, $result);
        }

        return $compiled;
    }

    /**
     * @param array $where
     * @return array
     */
    protected function compileWhereBasic(array $where)
    {
        extract($where);

        // Replace like with a Regex instance.
        if ($operator == 'like') {
            $operator = '=';

            // Convert to regular expression.
            $regex = preg_replace('#(^|[^\\\])%#', '$1.*', preg_quote($value));

            // Convert like to regular expression.
            if (!Str::startsWith($value, '%')) {
                $regex = '^' . $regex;
            }
            if (!Str::endsWith($value, '%')) {
                $regex = $regex . '$';
            }

            $value = new Regex($regex, 'i');
        } // Manipulate regexp operations.
        elseif (in_array($operator, ['regexp', 'not regexp', 'regex', 'not regex'])) {
            // Automatically convert regular expression strings to Regex objects.
            if (!$value instanceof Regex) {
                $e = explode('/', $value);
                $flag = end($e);
                $regstr = substr($value, 1, -(strlen($flag) + 1));
                $value = new Regex($regstr, $flag);
            }

            // For inverse regexp operations, we can just use the $not operator
            // and pass it a Regex instence.
            if (Str::startsWith($operator, 'not')) {
                $operator = 'not';
            }
        }

        if (!isset($operator) || $operator == '=') {
            $query = [$column => $value];
        } elseif (array_key_exists($operator, $this->operators)) {
            $query = [$column => [$this->operators[$operator] => $value]];
        } else {
            $query = [$column => ['$' . $operator => $value]];
        }

        return $query;
    }

    /**
     * @param array $where
     * @return array
     */
    protected function compileWhereNull(array $where)
    {
        $where['operator'] = '=';
        $where['value'] = null;

        return $this->compileWhereBasic($where);
    }

    /**
     * @param array $where
     * @return array
     */
    protected function compileWhereNotNull(array $where)
    {
        $where['operator'] = '!=';
        $where['value'] = null;

        return $this->compileWhereBasic($where);
    }

    /**
     * Convert a key to ObjectID if needed.
     *
     * @param  mixed $id
     * @return mixed
     */
    public static function convertKey($id)
    {
        if (is_string($id) && strlen($id) === 24 && ctype_xdigit($id)) {
            return new ObjectID($id);
        }

        return $id;
    }
}