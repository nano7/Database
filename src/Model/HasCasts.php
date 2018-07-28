<?php namespace Nano7\Database\Model;

use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Support\Str;
use MongoDB\BSON\UTCDateTime;
use Nano7\Database\Query\Builder;

trait HasCasts
{
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [];

    /**
     * @var string
     */
    protected $dateFormat = 'Y-m-d H:i:s';

    /**
     * Get the casts array.
     *
     * @return array
     */
    public function getCasts()
    {
        $defaults = [
            '_id' => 'string',
            static::CREATED_AT => 'datetime',
            static::UPDATED_AT => 'datetime,'
        ];

        return array_merge([], $defaults, $this->casts);
    }

    /**
     * Determine whether an attribute should be cast to a native type.
     *
     * @param  string  $key
     * @param  array|string|null  $types
     * @return bool
     */
    public function hasCast($key, $types = null)
    {
        if (array_key_exists($key, $this->getCasts())) {
            return $types ? in_array($this->getCastType($key), (array) $types, true) : true;
        }

        return false;
    }

    /**
     * Get the type of cast for a model attribute.
     *
     * @param  string  $key
     * @return string
     */
    protected function getCastType($key)
    {
        return trim(strtolower($this->getCasts()[$key]));
    }

    /**
     * Get an attribute and cast value to a native PHP type.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    protected function getCast($key, $value)
    {
        if (is_null($value)) {
            return $value;
        }

        $type   = $this->getCastType($key);
        $method = 'getCast' . Str::studly($type) . 'Type';

        if (method_exists($this, $method)) {
            return $this->$method($key, $value);
        }

        return $value;
    }

    /**
     * Set an attribute and cast value to a native PHP type.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    protected function setCast($key, $value)
    {
        if (is_null($value)) {
            return $value;
        }

        $type   = $this->getCastType($key);
        $method = 'setCast' . Str::studly($type) . 'Type';

        if (method_exists($this, $method)) {
            return $this->$method($key, $value);
        }

        return $value;
    }

    /**
     * GET Type string.
     * @param $key
     * @param $value
     * @return string
     */
    protected function getCastStringType($key, $value)
    {
        return trim((string) $value);
    }

    /**
     * GET Type integer.
     * @param $key
     * @param $value
     * @return int
     */
    protected function getCastIntType($key, $value)
    {
        return (int) $value;
    }

    /**
     * GET Type float.
     * @param $key
     * @param $value
     * @return float
     */
    protected function getCastFloatType($key, $value)
    {
        return (float) $value;
    }

    /**
     * GET Type boolean.
     * @param $key
     * @param $value
     * @return bool
     */
    protected function getCastBoolType($key, $value)
    {
        return (bool) $value;
    }

    /**
     * GET Type datetime.
     * @param $key
     * @param $value
     * @return Carbon
     */
    protected function getCastDateTimeType($key, $value)
    {
        // Verificar se jah eh Carbon
        if ($value instanceof Carbon) {
            return $value;
        }

        // Se for um UTCDateTime converter para Carbon
        if ($value instanceof UTCDateTime) {
            return Carbon::createFromTimestamp($value->toDateTime()->getTimestamp());
        }

        // Se for um DateTimeInterface converter para Carbon
        if ($value instanceof DateTimeInterface) {
            return new Carbon($value->format('Y-m-d H:i:s.u'), $value->getTimezone());
        }

        // Se foi passado um numerico com o timestamp converter para Carbon
        if (is_numeric($value)) {
            return Carbon::createFromTimestamp($value);
        }

        // Verificar se foi informado uma string no formato yyyy-mm-dd
        // Converter para carbon
        if (is_string($value) && preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value)) {
            return Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
        }

        // Verificar se foi informado uma string no formato hh:nn:ss
        // Converter para carbon
        if (is_string($value) && preg_match('/^(\d{2}):(\d{2})-(\d{2})$/', $value)) {
            return Carbon::createFromFormat('H:i:s', $value);
        }

        // Finally, we will just assume this date is in the format used by default on
        // the database connection and use that format to create the Carbon object
        // that is returned back out to the developers after we convert it here.
        return Carbon::createFromFormat($this->dateFormat, $value);
    }

    /**
     * GET Type date.
     * @param $key
     * @param $value
     * @return bool
     */
    protected function getCastDateType($key, $value)
    {
        return $this->getCastDateTimeType($key, $value)->startOfDay();
    }

    /**
     * GET Type time.
     * @param $key
     * @param $value
     * @return bool
     */
    protected function getCastTimeType($key, $value)
    {
        return $this->getCastDateTimeType($key, $value);
    }

    /**
     * SET Type string and _id.
     * @param $key
     * @param $value
     * @return string|mixed
     */
    protected function setCasStringType($key, $value)
    {
        if (($key == '_id') && (is_string($value))) {
            return Builder::convertKey($value);
        }

        return trim((string) $value);
    }

    /**
     * SET Type datetime.
     * @param $key
     * @param $value
     * @return UTCDateTime
     */
    protected function setCastDatetimeType($key, $value)
    {
        // Se ja for um UTCDateTime
        if ($value instanceof UTCDateTime) {
            return $value;
        }

        $value = $this->getCastDateTimeType($key, $value);

        return new UTCDateTime($value->getTimestamp() * 1000);
    }
}
