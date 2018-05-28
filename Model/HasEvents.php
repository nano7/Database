<?php namespace Nano7\Database\Model;

trait HasEvents
{
    /**
     * Make event name.
     *
     * @param $event
     * @return string
     */
    protected static function makeModelEventName($event)
    {
        $name = get_called_class();
        $event = sprintf('model.%s.%s', $event, $name);

        return $event;
    }

    /**
     * Register evento model.
     *
     * @param $event
     * @param $callback
     */
    protected static function registerModelEvent($event, $callback)
    {
        $event = self::makeModelEventName($event);

        event()->listen($event, $callback);
    }

    /**
     * Fire the given event for the model.
     *
     * @param  string  $event
     * @param  bool  $halt
     * @return mixed
     */
    protected function fireModelEvent($event, $halt = true)
    {
        $event = self::makeModelEventName($event);

        $result = event()->fire($event, [$this], $halt);

        if ($result === false) {
            return false;
        }

        return $result;
    }

    /**
     * Register a saving model event with the dispatcher.
     *
     * @param  \Closure|string  $callback
     * @return void
     */
    public static function saving($callback)
    {
        static::registerModelEvent('saving', $callback);
    }

    /**
     * Register a saved model event with the dispatcher.
     *
     * @param  \Closure|string  $callback
     * @return void
     */
    public static function saved($callback)
    {
        static::registerModelEvent('saved', $callback);
    }

    /**
     * Register an updating model event with the dispatcher.
     *
     * @param  \Closure|string  $callback
     * @return void
     */
    public static function updating($callback)
    {
        static::registerModelEvent('updating', $callback);
    }

    /**
     * Register an updated model event with the dispatcher.
     *
     * @param  \Closure|string  $callback
     * @return void
     */
    public static function updated($callback)
    {
        static::registerModelEvent('updated', $callback);
    }

    /**
     * Register a creating model event with the dispatcher.
     *
     * @param  \Closure|string  $callback
     * @return void
     */
    public static function creating($callback)
    {
        static::registerModelEvent('creating', $callback);
    }

    /**
     * Register a created model event with the dispatcher.
     *
     * @param  \Closure|string  $callback
     * @return void
     */
    public static function created($callback)
    {
        static::registerModelEvent('created', $callback);
    }

    /**
     * Register a deleting model event with the dispatcher.
     *
     * @param  \Closure|string  $callback
     * @return void
     */
    public static function deleting($callback)
    {
        static::registerModelEvent('deleting', $callback);
    }

    /**
     * Register a deleted model event with the dispatcher.
     *
     * @param  \Closure|string  $callback
     * @return void
     */
    public static function deleted($callback)
    {
        static::registerModelEvent('deleted', $callback);
    }
}
