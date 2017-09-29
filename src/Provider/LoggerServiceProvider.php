<?php declare(strict_types=1);

namespace Gmo\Web\Provider;

use Bolt\Collection\Arr;
use Bolt\Collection\MutableBag;
use Bolt\Common\Str;
use Gmo\Web\EventListener\HttpLogListener;
use Monolog\Handler\NullHandler;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Api\EventListenerProviderInterface;
use Symfony\Bridge\Monolog\Handler\DebugHandler;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Bridge\Monolog\Processor\DebugProcessor;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Basic Monolog services structure. Handlers and processors are left to be filled in.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class LoggerServiceProvider implements ServiceProviderInterface, EventListenerProviderInterface
{
    public function register(Container $app)
    {
        $app['logger'] = function ($app) {
            $handlers = Arr::from($app['logger.handlers']) ?: [new NullHandler()];
            $processors = Arr::from($app['logger.processors']);

            $logger = new Logger($app['logger.name'] ?? 'App', $handlers, $processors);

            // Add Debug Processor/Handler for Symfony's WebProfiler.
            // Pushed to logger here so they are processed first.
            if ($app['debug']) {
                if (class_exists(DebugProcessor::class)) {
                    // For Monolog Bridge v3.2+
                    $logger->pushProcessor(new DebugProcessor());
                } else {
                    // For Monolog Bridge v2.8-3.1
                    $logger->pushHandler(new DebugHandler());
                }
            }

            return $logger;
        };

        $app['logger.new'] = $app->protect(function ($name) use ($app) {
            return $app['logger']->withName(Str::className($name) ?: $name);
        });

        $app['logger.handlers'] = function ($app) {
            $handlers = new MutableBag();

            return $handlers;
        };

        $app['logger.processors'] = function ($app) {
            $processors = new MutableBag();

            return $processors;
        };

        $app['logger.listener.http'] = function ($app) {
            return new HttpLogListener($app['logger']);
        };
    }

    public function subscribe(Container $app, EventDispatcherInterface $dispatcher)
    {
        $dispatcher->addSubscriber($app['logger.listener.http']);
    }
}
