<?php declare(strict_types=1);

namespace Gmo\Web\Provider;

use Bolt\Collection\Arr;
use Bolt\Collection\MutableBag;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

class ConsoleServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['console'] = function ($app) {
            $console = new \Gmo\Common\Console\Application($app['console.name'], null, $app['container']);

            $console->setDispatcher($app['dispatcher']);

            if (isset($app['root_path'])) {
                $console->setProjectDirectory($app['root_path']);
            }

            $console->addCommands(Arr::from($app['console.commands']));

            return $console;
        };

        $app['console.name'] = 'UNKNOWN';

        $app['console.commands'] = function () {
            return new MutableBag();
        };
    }
}
