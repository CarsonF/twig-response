<?php declare(strict_types=1);

namespace Gmo\Web\Routing;

use Silex;
use Symfony\Component\Routing\RouteCollection;

/**
 * Extended ControllerCollection for these reasons:
 *
 * - Allow a route that starts with :: to go to the method specified in the current class.
 * - Allow a null route to default to specified class method.
 * - Split flush method to make it easier to override
 * - Remove trailing slash from flushed routes
 *
 * @method $this assert(string $variable, string $regexp)
 * @method $this value(string $variable, mixed $default)
 * @method $this convert(string $variable, mixed $callback)
 * @method $this method(string $method)
 * @method $this requireHttp()
 * @method $this requireHttps()
 * @method $this before(mixed $callback)
 * @method $this after(mixed $callback)
 * @method $this option(string $name, mixed $value)
 * @method $this secure(string|array ...$roles)
 */
class ControllerCollection extends Silex\ControllerCollection implements DefaultControllerAwareInterface
{
    /** @var string|object */
    protected $defaultControllerClass;
    /** @var string */
    protected $defaultControllerMethod;

    //region Override parent methods to return our subclass for IDE completion.

    /**
     * {@inheritdoc}
     *
     * @return Controller
     */
    public function get($pattern, $to = null)
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return parent::get($pattern, $to);
    }

    /**
     * {@inheritdoc}
     *
     * @return Controller
     */
    public function post($pattern, $to = null)
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return parent::post($pattern, $to);
    }

    /**
     * {@inheritdoc}
     *
     * @return Controller
     */
    public function put($pattern, $to = null)
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return parent::put($pattern, $to);
    }

    /**
     * {@inheritdoc}
     *
     * @return Controller
     */
    public function delete($pattern, $to = null)
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return parent::delete($pattern, $to);
    }

    /**
     * {@inheritdoc}
     *
     * @return Controller
     */
    public function options($pattern, $to = null)
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return parent::options($pattern, $to);
    }

    /**
     * {@inheritdoc}
     *
     * @return Controller
     */
    public function patch($pattern, $to = null)
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return parent::patch($pattern, $to);
    }

    //endregion

    /**
     * This uses default class/method if not provided
     *
     * {@inheritdoc}
     *
     * @return Controller
     */
    public function match($pattern, $to = null)
    {
        if (!$this->defaultControllerClass) {
            /** @noinspection PhpIncompatibleReturnTypeInspection */
            return parent::match($pattern, $to);
        }
        if ($to === null && $this->defaultControllerMethod) {
            $to = [$this->defaultControllerClass, $this->defaultControllerMethod];
        } elseif (is_string($to) && strpos($to, '::') === 0) {
            $to = [$this->defaultControllerClass, substr($to, 2)];
        }

        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return parent::match($pattern, $to);
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultControllerClass($class): void
    {
        $this->defaultControllerClass = $class;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultControllerMethod(string $method): void
    {
        $this->defaultControllerMethod = $method;
    }

    /**
     * {@inheritdoc}
     */
    public function flush($prefix = '')
    {
        return $this->flushCollection($prefix, $this, $this->routesFactory ?: new RouteCollection());
    }

    /**
     * Persists and freezes staged controllers.
     *
     * Note: This is similar logic to {@see \Silex\ControllerCollection::doFlush} just broken up into
     * multiple methods to make it easier to override.
     *
     * Note: This method has no side effects.
     *
     * @param string               $prefix
     * @param ControllerCollection $collection
     * @param RouteCollection      $routes
     *
     * @return RouteCollection
     */
    protected function flushCollection(string $prefix, ControllerCollection $collection, RouteCollection $routes): RouteCollection
    {
        $prefix = $this->normalizePrefix($prefix);

        foreach ($collection->controllers as $controller) {
            if ($controller instanceof Silex\Controller) {
                $this->flushController($prefix, $controller, $routes);
            } elseif ($controller instanceof ControllerCollection) {
                $this->flushSubCollection($prefix, $controller, $routes);
            } else {
                throw new \LogicException('Controllers need to be Controller or ControllerCollection instances');
            }
        }

        $collection->controllers = [];

        return $routes;
    }

    /**
     * Add the AbstractController to the RouteCollection and freeze it
     *
     * @param string           $prefix
     * @param Silex\Controller $controller
     * @param RouteCollection  $routes
     */
    protected function flushController(string $prefix, Silex\Controller $controller, RouteCollection $routes): void
    {
        // When mounting a controller class with a prefix most times you have a route with a blank path.
        // That is the only route that flushes to include an (unwanted) trailing slash.
        // This fixes that trailing slash.
        $controller->getRoute()->setPath(rtrim($prefix . $controller->getRoute()->getPath(), '/'));

        $this->generateControllerName($routes, $controller);
        $routes->add($controller->getRouteName(), $controller->getRoute());
        $controller->freeze();
    }

    /**
     * Add the ControllerCollection to the RouteCollection
     *
     * @param string               $prefix
     * @param ControllerCollection $collection
     * @param RouteCollection      $routes
     */
    protected function flushSubCollection(string $prefix, ControllerCollection $collection, RouteCollection $routes): void
    {
        $prefix .= $this->normalizePrefix($collection->prefix);
        $collection->flushCollection($prefix, $collection, $routes);
    }

    /**
     * Same logic as the first part of {@see RouteCollection::addPrefix}
     *
     * @param string $prefix
     *
     * @return string
     */
    protected function normalizePrefix(string $prefix): string
    {
        $prefix = trim(trim($prefix), '/');
        if (!empty($prefix)) {
            $prefix = '/' . $prefix;
        }

        return $prefix;
    }

    /**
     * Generate route name for controller if one does not exist
     *
     * Note: same code as in {@see Silex\ControllerCollection::flush}
     *
     * @param RouteCollection  $routes
     * @param Silex\Controller $controller
     */
    protected function generateControllerName(RouteCollection $routes, Silex\Controller $controller): void
    {
        if (!$name = $controller->getRouteName()) {
            $name = $controller->generateRouteName('');
            while ($routes->get($name)) {
                $name .= '_';
            }
            $controller->bind($name);
        }
    }
}
