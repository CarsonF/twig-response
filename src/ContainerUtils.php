<?php declare(strict_types=1);

namespace Gmo\Web;

use Pimple as Container;

/**
 * Extra Container utils.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
final class ContainerUtils
{
    /**
     * Modifies the $parameter with the $extender by wrapping the $serviceName definition.
     * This is useful when you want to modify the parameter with a the value that depends on
     * another service/parameter. Just be aware that this "extending" comes after other
     * modifications to the parameter, so it should only be used for default values.
     *
     * @param Container $app
     * @param string    $parameterName
     * @param string    $serviceName
     * @param callable  $extender
     */
    public static function extendParameter(
        Container $app,
        string $parameterName,
        string $serviceName,
        callable $extender
    ) {
        if (!isset($app[$parameterName]) || !isset($app[$serviceName])) {
            return;
        }

        $factory = $app->raw($serviceName);
        $app[$serviceName] = $app->share(
            function ($app) use ($parameterName, $factory, $extender) {
                $app[$parameterName] = $extender($app[$parameterName], $app);

                return $factory($app);
            }
        );
    }

    private function __construct()
    {
    }
}
