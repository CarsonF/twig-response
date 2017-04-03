<?php declare(strict_types=1);

namespace Gmo\Web\Routing;

interface DefaultControllerAwareInterface
{
    /**
     * Sets the default controller class.
     *
     * This is this first part of a callable so it can be a string or an object.
     *
     * @param string|object $class
     */
    public function setDefaultControllerClass($class): void;

    /**
     * Sets the default controller class method.
     *
     * This is this second part of a callable.
     *
     * @param string $method
     */
    public function setDefaultControllerMethod(string $method): void;
}
