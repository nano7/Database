<?php namespace Nano7\Database\Model;

class EmbedModel
{
    use HasCasts;
    use HasMutator;

    /**
     * @var
     */
    protected $id;

    /**
     * Indicates if the model exists.
     *
     * @var bool
     */
    public $exists = false;

    /**
     * @var \Closure
     */
    protected $callbackGet;

    /**
     * @var \Closure
     */
    protected $callbackSet;

    /**
     * @var \Closure
     */
    protected $callbackUnset;

    /**
     * @param $parent
     */
    public function __construct(\Closure $callbackGet, \Closure $callbackSet, \Closure $callbackUnset)
    {
        $this->callbackGet   = $callbackGet;
        $this->callbackSet   = $callbackSet;
        $this->callbackUnset = $callbackUnset;
    }

    /**
     * Create a new instance of the given model.
     *
     * @param  array  $attributes
     * @param  bool  $exists
     * @return EmbedModel
     */
    public function newInstance($attributes = [], $exists = false)
    {
        $model = new static($this->callbackGet, $this->callbackSet, $this->callbackUnset);
        $model->fill((array) $attributes);

        $this->id = array_key_exists('_id', $attributes) ? $attributes['_id'] : null;

        $model->exists = $exists;

        return $model;
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
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get a plain attribute (not a relationship).
     *
     * @param  string  $key
     * @return mixed
     */
    public function getAttribute($key)
    {
        $value = null;
        if ($this->callbackGet instanceof \Closure) {
            $value = call_user_func_array($this->callbackGet, [$key, $this->getId()]);
        }

        // Verificar se foi implementado um mutator
        if ($this->hasGetMutator($key)) {
            return $this->getMutateAttribute($key, $value);
        }

        // Verificar se foi definido o cast do key
        if ((! is_null($value)) && $this->hasCast($key)) {
            return $this->getCast($key, $value);
        }

        return $value;
    }

    /**
     * Set a given attribute on the model.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return $this
     */
    public function setAttribute($key, $value)
    {
        // First we will check for the presence of a mutator for the set operation
        // which simply lets the developers tweak the attribute as it is set on
        // the model, such as "json_encoding" an listing of data for storage.
        if ($this->hasSetMutator($key)) {
            $value = $this->setMutateAttribute($key, $value);
        } else {
            // Verificar se foi definido o cast do key
            if ($this->hasCast($key)) {
                $value = $this->setCast($key, $value);
            }
        }

        // Inserir documento e capturar ID
        if ($this->callbackSet instanceof \Closure) {
            $id = call_user_func_array($this->callbackSet, [$key, $value, $this->getId()]);
            if (! is_null($id)) {
                $this->id = $id;
            }
        }
    }

    /**
     * Delete the model from the database.
     *
     * @return bool
     */
    public function delete()
    {
        if ($this->callbackUnset instanceof \Closure) {
            return call_user_func_array($this->callbackUnset, [$this->getId()]);
        }

        return false;
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