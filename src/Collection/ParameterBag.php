<?php declare(strict_types=1);

namespace Gmo\Web\Collection;

use Carbon\Carbon;
use Gmo\Web\Exception\InvalidKeyException;
use Gmo\Web\Exception\InvalidParameterException;
use Gmo\Web\Exception\MissingParameterException;
use Gmo\Web\Exception\NoKeyException;
use LogicException;
use Symfony\Component\HttpFoundation\ParameterBag as ParameterBagBase;
use Traversable;

/**
 * {@inheritdoc}
 *
 * Additional methods have been added.
 */
class ParameterBag extends ParameterBagBase
{
    protected $required = false;

    /**
     * Constructor.
     *
     * @param iterable $parameters An array of parameters
     */
    public function __construct(iterable $parameters = [])
    {
        if ($parameters instanceof Traversable) {
            $parameters = iterator_to_array($parameters);
        }

        parent::__construct($parameters);
    }

    /**
     * Sets the next parameter retrieved to be required or not.
     *
     * @param bool $required
     *
     * @return ParameterBag
     */
    public function required(bool $required = true): ParameterBag
    {
        $this->required = $required;

        return $this;
    }

    /**
     * Returns a parameter by name.
     *
     * @param string $key     The key
     * @param mixed  $default The default value if the parameter key does not exist
     *
     * @throws MissingParameterException If the parameter is required and does not exist.
     * @throws InvalidParameterException If the parameter is required and empty.
     *
     * @return mixed
     */
    public function get($key, $default = null, $deep = false)
    {
        if (!$this->required) {
            return parent::get($key, $default);
        }
        $this->required = false;

        if (!$this->has($key)) {
            throw new MissingParameterException($key);
        }

        $value = parent::get($key);

        if (empty($value)) {
            throw new InvalidParameterException($key, '%s should not be empty');
        }

        return $value;
    }

    /**
     * Wrapper to throw NoKeyException if API key does not exist.
     *
     * @param string $keyName
     *
     * @throws InvalidKeyException
     * @throws NoKeyException
     *
     * @return mixed
     */
    public function getApiKey(string $keyName = 'key')
    {
        try {
            return $this->required()->get($keyName);
        } catch (MissingParameterException $e) {
            throw new NoKeyException($keyName);
        } catch (InvalidParameterException $e) {
            throw new InvalidKeyException($keyName);
        }
    }

    /**
     * Returns a parameter value converted to a Carbon instance.
     *
     * If the default value is an int or string it will be converted to a Carbon instance.
     *
     * @param string                 $key     The key
     * @param Carbon|int|string|null $default The default value if the parameter key doesn't exist or is empty
     * @param string                 $tz      The timezone to create the Carbon instances with.
     *
     * @throws InvalidParameterException If the parameter value fails to parse
     * @throws LogicException If Carbon is not installed
     *
     * @return Carbon|null A Carbon timestamp or null if the key was empty and default is null
     */
    public function getTimestamp(string $key, $default = null, string $tz = null): ?Carbon
    {
        $timestamp = $this->get($key);
        if (!empty($timestamp)) {
            try {
                if (is_numeric($timestamp)) {
                    $carbon = Carbon::createFromTimestamp($timestamp, $tz);
                } else {
                    $carbon = new Carbon($timestamp, $tz);
                }
            } catch (\Exception $e) {
                throw new InvalidParameterException($key);
            }
        } else {
            if ($default === null) {
                return null;
            }
            if (!$default instanceof Carbon) {
                $carbon = Carbon::createFromTimestamp($default, $tz);
            } else {
                $carbon = $default;
            }
        }

        // Assert date is in valid range
        $carbon = max($carbon, Carbon::create(1000, 1, 1, 0, 0, 0));
        $carbon = min($carbon, Carbon::create(9999, 12, 31, 23, 59, 59));

        return $carbon;
    }
}
