<?php namespace Nano7\Database\Model;

use Nano7\Foundation\Support\Arr;
use Nano7\Foundation\Support\Str;
use Nano7\Validation\Json\ValidatorJson;
use Nano7\Validation\ValidationException;
use Illuminate\Contracts\Support\Arrayable;
use Nano7\Database\Query\Builder as QueryBuilder;

class Model implements Arrayable
{
    use HasCasts;
    use HasEvents;
    use HasScopes;
    use HasMutator;
    use HasRelation;
    use HasTimestamps;
    use HasAttributes;
    use HasHideAttributes;

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
     * @var array
     */
    protected static $scopes = [];

    /**
     * The array of booted models.
     *
     * @var array
     */
    protected static $booted = [];

    /**
     * @var bool
     */
    protected $fillExists = false;

    /**
     * The name of the "created at" column.
     *
     * @var string
     */
    const CREATED_AT = 'created_at';

    /**
     * The name of the "updated at" column.
     *
     * @var string
     */
    const UPDATED_AT = 'updated_at';

    /**
     * Create a new Model model instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->fireModelEvent('initializing', false);

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
     * @param  bool $exists
     * @return $this
     */
    public function fill(array $attributes, $exists = false)
    {
        $this->fillExists = $exists;

        foreach ($attributes as $key => $value) {
            $this->setAttribute($key, $value);
        }

        $this->fillExists = false;

        return $this;
    }

    /**
     * Create clone this model.
     *
     * @return Model
     */
    public function toClone()
    {
        $attributes = $this->toArray(false);

        unset($attributes['id']);
        unset($attributes['_id']);

        return $this->create($attributes, false);
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
     *
     * @param array $ignoreScopes
     * @return Builder
     */
    protected function newQuery($ignoreScopes = [])
    {
        $query = new Builder();
        $query->setModel($this);
        $query->setQuery($this->newQueryNotModel($ignoreScopes));

        return $query;
    }

    /**
     * Get new Query.
     *
     * @param array $ignoreScopes
     * @return QueryBuilder
     */
    protected function newQueryNotModel($ignoreScopes = [])
    {
        $query = $this->connection()->collection($this->getCollection());

        // Adicionar escopos globais
        $this->applyScopes($query, $this, $ignoreScopes);

        return $query;
    }

    /**
     * Get new query of model.
     *
     * @param array $ignoreScopes
     * @return Builder
     */
    public static function query($ignoreScopes = [])
    {
        return (new static)->newQuery($ignoreScopes);
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
        $model->fill((array) $attributes, $exists);
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

        // Verificar para atualizar os timestamps
        if ($this->usesTimestamps()) {
            $this->updateTimestamps();
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

        // Verificar para atualizar os timestamps
        if ($this->usesTimestamps()) {
            $this->updateTimestamps();
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
        $query = $this->newQueryNotModel(['*']);

        // Validar attributes
        if ($this->validate() != true) {
            return false;
        }

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
        $query = $this->newQueryNotModel(['*']);

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
     * Validate attributes.
     *
     * @return bool
     */
    public function validate()
    {
        // Disparar evento que o documento esta sendo validado
        if ($this->fireModelEvent('validating') === false) {
            return false;
        }

        // Varificar se tem o arquivo schema
        $validator = new ValidatorJson(app_path('Models/Schemas'), ['array_equal_object' => true]);
        $class = Arr::last(explode('\\', get_called_class()));
        $schema = $class . 'Schema';

        // Verificar se schema foi implementado
        if (! $validator->existsSchema($schema)) {
            return true;
        }

        // Validar schema
        if (! $validator->validate($this->attributes, $schema, strtolower($class))) {
            throw new ValidationException("Erro on validate model", null, null, $validator->getErros());
        }

        $this->fireModelEvent('validated', false);

        return true;
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray($toPersist = true)
    {
        $array = [];

        foreach (array_keys($this->attributes) as $key) {
            $key   = ($key == '_id') ? 'id' : $key;
            $value = $this->getAttribute($key);
            $value = $toPersist ? $this->getCastToPersistCast($key, $value) : $value;

            // Verificar eh um campo escondido
            if ((! $toPersist) || ($toPersist && (! $this->hasHidden($key)))) {
                $array[$key] = $value;
            }
        }

        return $array;
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