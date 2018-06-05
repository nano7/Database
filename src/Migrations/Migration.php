<?php namespace Nano7\Database\Migrations;

abstract class Migration
{
    /**
     * The name of the database connection to use.
     *
     * @var string
     */
    protected $connection;

    /**
     * Get the migration connection name.
     *
     * @return string
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * @return \Nano7\Database\ConnectionInterface
     */
    public function connection()
    {
        return db()->connection($this->getConnection());
    }

    /**
     * Run Up Migration.
     */
    abstract public function up();
}
