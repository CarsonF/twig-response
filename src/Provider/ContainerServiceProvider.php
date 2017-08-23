<?php declare(strict_types=1);

namespace Gmo\Web\Provider;

use Acclimate\Container\Adapter\ArrayAccessContainerAdapter;
use Silex\Application;
use Silex\ServiceProviderInterface;

class ContainerServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        // Returns PSR-11 Container
        $app['container'] = $app->share(function ($app) {
            return new ArrayAccessContainerAdapter($app);
        });
    }

    public function boot(Application $app)
    {
    }
}
