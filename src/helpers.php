<?php

if (! function_exists('db')) {
    /**
     * @param null $connection
     * @return \Nano7\Database\DataManager|\Nano7\Database\ConnectionInterface
     */
    function db($connection = null)
    {
        $db = app('db');

        if (is_null($connection)) {
            return $db;
        }

        return $db->connection($connection);
    }
}