<?php namespace Nano7\Database\Deploys;

use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Nano7\Foundation\Support\Filesystem;

class Deployer
{
    /**
     * The filesystem instance.
     *
     * @var Filesystem
     */
    protected $files;

    /**
     * The paths to all of the deploy files.
     *
     * @var array
     */
    protected $paths = [];

    /**
     * The notes for the current operation.
     *
     * @var array
     */
    protected $notes = [];

    /**
     * @var array
     */
    protected $collections = [];

    /**
     * Create a new delopyer instance.
     *
     * @param  Filesystem  $files
     * @return void
     */
    public function __construct(Filesystem $files)
    {
        $this->files = $files;
    }

    /**
     * Run the pending deployies at a given path.
     *
     * @param  array|string  $paths
     * @param  array  $options
     * @return array
     */
    public function run($paths = [], array $options = [])
    {
        $this->notes = [];

        $files = $this->getDeployFiles($paths);

        $this->requireFiles($files);

        $this->runDeployies($files, $options);

        $this->runClearDatabase();

        return $files;
    }

    /**
     * Run an array of deployies.
     *
     * @param  array  $deployies
     * @param  array  $options
     * @return void
     */
    protected function runDeployies(array $deployies, array $options = [])
    {
        if (count($deployies) == 0) {
            $this->note('<info>Nothing to deploy.</info>');

            return;
        }

        foreach ($deployies as $file) {
            $this->runDeploy($file);
        }
    }

    /**
     * Run "run" a deploy instance.
     *
     * @param  string  $file
     * @return void
     */
    protected function runDeploy($file)
    {
        $deploy = $this->resolve($name = $this->getDeployName($file));

        $this->note("<comment>Deploying:</comment> {$name}");

        // Run deploy
        $deploy->run();

        $this->note("<info>Deployed:</info>  {$name}");
    }

    /**
     * Run clear database.
     */
    protected function runClearDatabase()
    {
        $collections = db()->getCollections();
    }

    /**
     * Resolve a deploy instance from a file.
     *
     * @param  string  $file
     * @return object
     */
    public function resolve($file)
    {
        $class = Str::studly($file);

        return new $class($this);
    }

    /**
     * Get all of the deploy files in a given path.
     *
     * @param  string|array  $paths
     * @return array
     */
    public function getDeployFiles($paths)
    {
        return Collection::make($paths)->flatMap(function ($path) {
            return $this->files->glob($path.'/*.php');
        })->filter()->sortBy(function ($file) {
                return $this->getDeployName($file);
            })->values()->keyBy(function ($file) {
                return $this->getDeployName($file);
            })->all();
    }

    /**
     * Require in all the deploy files in a given path.
     *
     * @param  array   $files
     * @return void
     */
    public function requireFiles(array $files)
    {
        foreach ($files as $file) {
            $this->files->requireOnce($file);
        }
    }

    /**
     * Get the name of the deploy.
     *
     * @param  string  $path
     * @return string
     */
    public function getDeployName($path)
    {
        return str_replace('.php', '', basename($path));
    }

    /**
     * Register a custom deploy path.
     *
     * @param  string  $path
     * @return void
     */
    public function path($path)
    {
        $this->paths = array_unique(array_merge($this->paths, [$path]));
    }

    /**
     * Get all of the custom deploy paths.
     *
     * @return array
     */
    public function paths()
    {
        return $this->paths;
    }

    /**
     * Get the file system instance.
     *
     * @return \Illuminate\Filesystem\Filesystem
     */
    public function getFilesystem()
    {
        return $this->files;
    }

    /**
     * Raise a note event for the deployer.
     *
     * @param  string  $message
     * @return void
     */
    public function note($message)
    {
        $this->notes[] = $message;
    }

    /**
     * Get the notes for the last operation.
     *
     * @return array
     */
    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * Create collection.
     * @param $name
     * @return bool
     */
    public function collection($name)
    {
        // Verificar se deve adicionar a colecao na lista
        if (! array_key_exists($name, $this->collections)) {
            $this->collections[$name] = [];
        }

        // Verificar se jah foi criado
        if (db()->hasCollection($name)) {
            return true;
        }

        // Criar colecao
        db()->createCollection($name);

        return true;
    }

    /**
     * Create index.
     * @param $name
     * @return bool
     */
    public function index($collection, $columns, $unique)
    {
        // Montar nome do index
        $name = strtolower(sprintf('ax_%s', implode('_', array_keys($columns))));

        // Verificar se deve adicionar a colecao na lista
        if (! array_key_exists($collection, $this->collections)) {
            $this->collections[$collection] = [];
        }

        // Verificar se deve adicionar a index na lista
        if (! in_array($name, $this->collections[$collection])) {
            $this->collections[$collection][] = $name;
        }

        // Verificar se ja foi criado o index
        if (db()->hasIndex($collection, $name)) {
            return true;
        }

        // Criar indice
        db()->createIndex($collection, $columns, ['name' => $name, 'unique' => $unique]);

        return true;
    }
}