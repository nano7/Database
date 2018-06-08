<?php namespace Nano7\Database\Model;

use Nano7\Foundation\Support\Str;
use Nano7\Database\Query\Builder as QueryBuilder;

class Model
{
    use HasCasts;
    use HasEvents;
    use HasScopes;
    use HasMutator;
    use HasAttributes;
    use HasRelation;

    /**
     * The connection name for the model.
     *
     * @var string|null
     */
    protected $connection = null;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $collection;

    /**
     * Indicates if the model exists.
     *
     * @var bool
     */
    public $exists = false;

    /**
     * The array of booted models.
     *
     * @var array
     */
    protected static $booted = [];

    /**
     * Create a new Model model instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->bootIfNotBooted();
    }

    /**
     * Check if the model needs to be booted and if so, do it.
     *
     * @return void
     */
    protected function bootIfNotBooted()
    {
        if (! isset(static::$booted[static::class])) {
            static::$booted[static::class] = true;

            $this->fireModelEvent('booting', false);

            static::boot();

            $this->fireModelEvent('booted', false);
        }
    }

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        static::bootTraits();
    }

    /**
     * Boot all of the bootable traits on the model.
     *
     * @return void
     */
    protected static function bootTraits()
    {
        $class = static::class;

        foreach (class_uses_recursive($class) as $trait) {
            if (method_exists($class, $method = 'boot' . class_basename($trait))) {
                forward_static_call([$class, $method]);
            }
        }
    }

    /**
     * @param array $attributes
     * @param bool $save
     * @return Model
     */
    public static function create(array $attributes, $save = true)
    {
        $instance = (new static);
        $instance->fill($attributes);

        if ($save) {
            $instance->save();
        }

        return $instance;
    }

    /**
     * Fill the model with an array of attributes.
     *
     * @param  array  $attributes
     * @return $this
     */
    public function fill(array $attributes)
    {
        foreach ($attributes as $key => $value) {
            $this->setAttribute($key, $value);
        }

        return $this;
    }

    /**
     * Get the collection associated with the model.
     *
     * @return string
     */
    public function getCollection()
    {
        if (! isset($this->collection)) {
            return str_replace(
                '\\', '', Str::snake(Str::plural(class_basename($this)))
            );
        }

        return $this->collection;
    }

    /**
     * Set the collection associated with the model.
     *
     * @param  string  $collection
     * @return $this
     */
    public function setCollection($collection)
    {
        $this->collection = $collection;

        return $this;
    }

    /**
     * @return \Nano7\Database\ConnectionInterface
     */
    protected function connection()
    {
        return db($this->getConnectionName());
    }

    /**
     * @param $connectionName
     * @return $this
     */
    public function setConnection($connectionName)
    {
        $this->connection = $connectionName;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getConnectionName()
    {
        return $this->connection;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->getAttribute('_id');
    }

    /**
     * Get new Query.
     * @return Builder
     */
    protected function newQuery()
    {
        $query = new Builder();
        $query->setModel($this);
        $query->setQuery($this->newQueryNotModel());

        return $query;
    }

    /**
     * Get new Query.
     * @return QueryBuilder
     */
    protected function newQueryNotModel()
    {
        $query = $this->connection()->collection($this->getCollection());

        // Adicionar escopos globais
        $this->applyScopes($query, $this);

        return $query;
    }

    /**
     * @return Builder
     */
    public static function query()
    {
        return (new static)->newQuery();
    }

    /**
     * Create a new instance of the given model.
     *
     * @param  array  $attributes
     * @param  bool  $exists
     * @return Model
     */
    public function newInstance($attributes = [], $exists = false)
    {
        $model = new static();
        $model->fill((array) $attributes);
        $model->syncOriginal();

        $model->exists = $exists;

        $model->setConnection($this->getConnectionName());

        return $model;
    }

    /**
     * Processar inserção do documento..
     *
     * @param  QueryBuilder  $query
     * @return bool
     */
    protected function performInsert(QueryBuilder $query)
    {
        // Disparar evento de documento sendo criado
        if ($this->fireModelEvent('creating') === false) {
            return false;
        }

        // Inserir documento e capturar ID
        $id = $query->insertGetId($this->attributes);
        $this->setAttribute('_id', $id);

        // Marcar como model já carregou o documento
        $this->exists = true;

        // Disparar evento de documento criado
        $this->fireModelEvent('created', false);

        return true;
    }

    /**
     * Processar alteração do documento.
     *
     * @param  QueryBuilder  $query
     * @return bool
     */
    protected function performUpdate(QueryBuilder $query)
    {
        // Dosparar evento de documento sendo alterado
        if ($this->fireModelEvent('updating') === false) {
            return false;
        }

        // Carregar lista só dos atributos que foram alterados
        $changes = $this->getChanged();

        if (count($changes) > 0) {
            $query->where('_id', $this->getId());
            $query->update($changes);

            // Disparar evento de documento alterado
            $this->fireModelEvent('updated', false);
        }

        return true;
    }

    /**
     * Processar exclusao do documento.
     *
     * @param  QueryBuilder $query
     * @return bool
     */
    protected function performDelete(QueryBuilder $query)
    {
        // Verificar se documento existe (que já foi carregado ou salvo)
        if (! $this->exists) {
            return true;
        }

        // Disparar evento que o documento esta sendo excluido
        if ($this->fireModelEvent('deleting') === false) {
            return false;
        }

        // Excluir registro
        $query->where('_id', $this->getId());
        $query->delete();

        // Marcar como documento nao existe
        $this->exists = false;

        // Disparar evento que o documento foi excluido
        $this->fireModelEvent('deleted', false);

        return true;
    }

    /**
     * Save the model to the database.
     *
     * @return bool
     */
    public function save()
    {
        $query = $this->newQueryNotModel();

        // Disparar evento que o documento esta sendo salvo
        if ($this->fireModelEvent('saving') === false) {
            return false;
        }

        // Verificar se deve atualizar registro ou inserir um novo
        if ($this->exists) {
            $saved = $this->hasChanged() ? $this->performUpdate($query) : true;
        } else {
            $saved = $this->performInsert($query);
        }

        // Se documento foi salvo, finalizar com evento
        if ($saved) {
            // Disparar evento que documento foi salvo
            $this->fireModelEvent('saved', false);

            // SIncronizar orifinais
            $this->syncOriginal();
        }

        return $saved;
    }

    /**
     * Delete the model from the database.
     *
     * @return bool
     */
    public function delete()
    {
        $query = $this->newQueryNotModel();

        return $this->performDelete($query);
    }

    /**
     * Destroy the models for the given IDs.
     *
     * @param  array|int  $ids
     * @return int
     */
    public static function destroy($ids)
    {
        $count = 0;

        $ids = is_array($ids) ? $ids : func_get_args();

        // Criar instancia do model
        $instance = new static;

        foreach ($instance->query()->whereIn('_id', $ids)->get() as $model) {
            if ($model->delete()) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @param $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->getAttribute($name);
    }

    /**
     * @param $name
     * @param $value
     * @return $this
     */
    public function __set($name, $value)
    {
        return $this->setAttribute($name, $value);
    }
}