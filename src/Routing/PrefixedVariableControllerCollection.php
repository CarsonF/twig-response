<?php declare(strict_types=1);

namespace Gmo\Web\Routing;

use Silex\Controller;
use Silex\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Extends ControllerCollection to automatically:
 * 1) Prefix all routes with a variable
 * 2) Create a separate variable-less route for each route
 *
 * The bound route name is on the route with the prefix.
 */
abstract class PrefixedVariableControllerCollection extends ControllerCollection
{
    /** @var string|null The requirement for the variable */
    protected $variableRequirement = null;

    public function __construct(Route $defaultRoute, string $variableRequirement = null)
    {
        parent::__construct($defaultRoute);
        $this->variableRequirement = $variableRequirement;
    }

    /**
     * Returns the variable name
     *
     * @return string
     */
    abstract protected function getVariableName(): string;

    protected function getVariableRequirement(): ?string
    {
        return $this->variableRequirement;
    }

    protected function getVariablePrefix(string $prefix): string
    {
        return sprintf('/{%s}%s', $this->getVariableName(), $prefix);
    }

    protected function flushController(string $prefix, Controller $controller, RouteCollection $routes): void
    {
        $requirement = $this->getVariableRequirement();
        if ($requirement && !$controller->getRoute()->hasRequirement($this->getVariableName())) {
            $controller->assert($this->getVariableName(), $requirement);
        }

        // Clone current controller for unprefixed route
        $unprefixedController = new Controller(clone $controller->getRoute());

        parent::flushController($this->getVariablePrefix($prefix), $controller, $routes);
        parent::flushController($prefix, $unprefixedController, $routes);

        // Set real route name that will be used if route is matched
        $unprefixedController->getRoute()->setDefault(
            sprintf('_prefixed_route', $this->getVariableName()),
            $controller->getRouteName()
        );
    }

    /**
     * Flushes the sub-collection with current class logic instead of its own
     *
     * @param string $prefix
     * @param ControllerCollection $collection
     * @param RouteCollection $routes
     */
    protected function flushSubCollection(string $prefix, ControllerCollection $collection, RouteCollection $routes): void
    {
        $prefix .= $this->normalizePrefix($collection->prefix);
        $this->flushCollection($prefix, $collection, $routes);
    }
}
