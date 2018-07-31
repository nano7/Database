<?php namespace Nano7\Database\Model;

trait HasHideAttributes
{
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * @param $key
     * @return bool
     */
    protected function hasHidden($key)
    {
        return in_array($key, $this->hidden);
    }
}
