<?php namespace Nano7\Database\Model;

trait HasEvents
{
    /**
     * User exposed observable events.
     *
     * These are extra user-defined events observers may subscribe to.
     *
     * @var array
     */
    protected $observables = [];


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
     * Register a booting model event with the dispatcher.
     *
     * @param  \Closure|string  $callback
     * @return void
     */
    public static function booting($callback)
    {
        static::registerModelEvent('booting', $callback);
    }

    /**
     * Register a booted model event with the dispatcher.
     *
     * @param  \Closure|string  $callback
     * @return void
     */
    public static function booted($callback)
    {
        static::registerModelEvent('booted', $callback);
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

    /**
     * Register observers with the model.
     *
     * @param  object|array|string  $classes
     * @return void
     */
    public static function observe($classes)
    {
        $instance = new static;

        foreach ((array) $classes as $class) {
            $instance->registerObserver($class);
        }
    }

    /**
     * Register a single observer with the model.
     *
     * @param  object|string $class
     * @return void
     */
    protected function registerObserver($class)
    {
        $className = is_string($class) ? $class : get_class($class);

        foreach ($this->getObservableEvents() as $event) {
            if (method_exists($class, $event)) {
                static::registerModelEvent($event, $className . '@' . $event);
            }
        }
    }

    /**
     * Get the observable event names.
     *
     * @return array
     */
    public function getObservableEvents()
    {
        return array_merge(
            [
                'creating', 'created',
                'updating', 'updated',
                'saving',   'saved',
                'deleting', 'deleted',
            ],
            $this->observables
        );
    }
}
