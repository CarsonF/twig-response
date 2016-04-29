<?php declare(strict_types=1);

namespace Gmo\Web\Collection;

use BadMethodCallException;

/**
 * A parameter bag that cannot be modified.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class ImmutableParameterBag extends ParameterBag
{
    /**
     * @internal
     *
     * @throws BadMethodCallException
     */
    public function replace(array $parameters = [])
    {
        throw new BadMethodCallException('Cannot replace values on an ' . __CLASS__);
    }

    /**
     * @internal
     *
     * @throws BadMethodCallException
     */
    public function add(array $parameters = [])
    {
        throw new BadMethodCallException('Cannot add values on an ' . __CLASS__);
    }

    /**
     * @internal
     *
     * @throws BadMethodCallException
     */
    public function set($key, $value)
    {
        throw new BadMethodCallException('Cannot set values on an ' . __CLASS__);
    }

    /**
     * @internal
     *
     * @throws BadMethodCallException
     */
    public function remove($key)
    {
        throw new BadMethodCallException('Cannot remove values on an ' . __CLASS__);
    }
}
