<?php namespace Nano7\Database\Console;

use Nano7\Console\Command;
use Nano7\Database\Deploys\Deployer;

class DeployCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:deploy';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the database deployies';

    /**
     * The deployer instance.
     *
     * @var Deployer
     */
    protected $deployer;

    /**
     * Create a new migration command instance.
     *
     * @param  Deployer  $deployer
     * @return void
     */
    public function __construct(Deployer $deployer)
    {
        parent::__construct();

        $this->deployer = $deployer;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        // Next, we will check to see if a path option has been defined. If it has
        // we will use the path relative to the root of this installation folder
        // so that migrations may be run for any path within the applications.
        $this->deployer->run($this->getDeployPaths(), []);

        // Once the migrator has run we will grab the note output and send it out to
        // the console screen, since the migrator itself functions without having
        // any instances of the OutputInterface contract passed into the class.
        foreach ($this->deployer->getNotes() as $note) {
            $this->output->writeln($note);
        }
    }

    /**
     * Get all of the deploy paths.
     *
     * @return array
     */
    protected function getDeployPaths()
    {
        $deploy_path = app_path('deploys');

        return array_merge([$deploy_path], $this->deployer->paths());
    }
}
