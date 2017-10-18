<?php declare(strict_types=1);

namespace Gmo\Web\Routing;

use Silex\Route\SecurityTrait;

/**
 * Added more shortcut methods.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class Route extends \Silex\Route
{
    use SecurityTrait {
        secure as parentSecure;
    }

    /**
     * Secure the route with the given role(s).
     *
     * @param string[]|array ...$roles
     *
     * @return Route
     */
    public function secure(...$roles)
    {
        // Compatibility with parent which expects an array instead of variadic parameters.
        if (!empty($roles) && is_array($roles[0])) {
            $roles = $roles[0];
        }

        $this->parentSecure($roles);

        return $this;
    }

    /**
     * Sets an option value.
     *
     * @param string $name
     * @param mixed  $value
     *
     * @return Route
     */
    public function option(string $name, mixed $value)
    {
        $this->setOption($name, $value);

        return $this;
    }
}
