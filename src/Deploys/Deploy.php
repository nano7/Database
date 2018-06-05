<?php namespace Nano7\Database\Migrations;

use Nano7\Database\Deploys\Deployer;

abstract class Deploy
{
    /**
     * @var Deployer
     */
    protected $deployer;

    /**
     * @param Deployer $deployer
     */
    public function __construct(Deployer $deployer)
    {
        $this->deployer = $deployer;
    }

    /**
     * Run Deploy.
     */
    abstract public function run();
}
