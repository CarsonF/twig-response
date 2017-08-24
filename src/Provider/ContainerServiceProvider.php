<?php declare(strict_types=1);

namespace Gmo\Web\Provider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;

class ContainerServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        // Returns PSR-11 Container
        $app['container'] = function ($app) {
            return new \Pimple\Psr11\Container($app);
        };
    }
}
