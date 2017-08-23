<?php declare(strict_types=1);

namespace Gmo\Web\Provider;

use Bolt\Collection\Arr;
use Bolt\Collection\MutableBag;
use Silex\Application;
use Silex\ServiceProviderInterface;

class ConsoleServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['console'] = $app->share(function ($app) {
            $console = new \Gmo\Common\Console\Application($app['console.name'], null, $app['container']);

            $console->setDispatcher($app['dispatcher']);

            if (isset($app['root_path'])) {
                $console->setProjectDirectory($app['root_path']);
            }

            $console->addCommands(Arr::from($app['console.commands']));

            return $console;
        });

        $app['console.name'] = 'UNKNOWN';

        $app['console.commands'] = $app->share(function () {
            return new MutableBag();
        });
    }

    public function boot(Application $app)
    {
    }
}
