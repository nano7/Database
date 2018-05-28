<?php namespace Nano7\Database\Model;

use Nano7\Foundation\Support\Str;

trait HasMutator
{
    /**
     * Determine if a get mutator exists for an attribute.
     *
     * @param  string  $key
     * @return bool
     */
    protected function hasGetMutator($key)
    {
        return method_exists($this, 'get' . Str::studly($key) . 'Attribute');
    }

    /**
     * Get the value of an attribute using its mutator.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    protected function getMutateAttribute($key, $value)
    {
        return $this->{'get' . Str::studly($key) . 'Attribute'}($value);
    }

    /**
     * Determine if a set mutator exists for an attribute.
     *
     * @param  string  $key
     * @return bool
     */
    protected function hasSetMutator($key)
    {
        return method_exists($this, 'set'.Str::studly($key).'Attribute');
    }

    /**
     * Set the value of an attribute using its mutator.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    protected function setMutateAttribute($key, $value)
    {
        return $this->{'set' . Str::studly($key) . 'Attribute'}($value);
    }
}
