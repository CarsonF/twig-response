<?php declare(strict_types=1);

namespace Gmo\Web\Routing;

/**
 * This class isn't actually used. It just provides completion for IDE.
 * Parent class forwards calls on to {@see \Gmo\Web\Routing\Route} which is used.
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
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
final class Controller extends \Silex\Controller
{
}
