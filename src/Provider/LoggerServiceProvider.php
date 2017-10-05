<?php declare(strict_types=1);

namespace Gmo\Web\Provider;

use Bolt\Collection\Arr;
use Bolt\Collection\MutableBag;
use Bolt\Common\Str;
use Gmo\Common\Log\Handler\FallbackHandler;
use Gmo\Web\EventListener\HttpLogListener;
use Gmo\Web\Logger\Formatter\LogstashFormatter;
use Gmo\Web\Logger\Processor;
use Monolog\Handler\FingersCrossedHandler;
use Monolog\Handler\NullHandler;
use Monolog\Handler\RedisHandler;
use Monolog\Handler\SyslogHandler;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Predis;
use Psr\Log\LogLevel;
use Silex\Api\EventListenerProviderInterface;
use Symfony\Bridge\Monolog\Handler\DebugHandler;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Bridge\Monolog\Processor\DebugProcessor;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Monolog services.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class LoggerServiceProvider implements ServiceProviderInterface, EventListenerProviderInterface
{
    private const CLI = PHP_SAPI === 'cli';

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

            if (($env = $app['environment']) && ($env === 'production' || $env === 'staging')) {
                $handlers[] = $app['logger.handler.logstash'];
            }

            return $handlers;
        };

        $app['logger.processors'] = function ($app) {
            $processors = new MutableBag();

            $processors[] = new Processor\JsonSerializableProcessor();

            return $processors;
        };

        $app['logger.listener.http'] = function ($app) {
            return new HttpLogListener($app['logger']);
        };


        //region Processors

        $app['logger.processor.env'] = function ($app) {
            return new Processor\ConstantProcessor('env', $app['environment']);
        };

        $app['logger.processor.request'] = function ($app) {
            return new Processor\RequestProcessor($app['request_stack']);
        };

        //endregion

        //region Handlers

        $app['logger.redis'] = function () {
            return new Predis\Client(['database' => 4]);
        };

        //region Logstash

        $app['logger.handler.logstash'] = function ($app) {
            $handler = new RedisHandler(
                $app['logger.redis'],
                $app['logger.handler.logstash.key'] ?? 'logging',
                // Keep everything for web requests since they are filtered down with the
                // FingersCrossedHandler using the `logger.handler.logstash.level` value.
                self::CLI ? $app['logger.handler.logstash.level'] : Logger::DEBUG,
                true,
                $app['logger.handler.logstash.cap_size'] ?? 10000
            );

            // Fallback to syslog handler on Redis server/connection exceptions
            $handler = new FallbackHandler(
                $handler,
                $app['logger.handler.syslog'],
                [
                    Predis\Connection\ConnectionException::class,
                    Predis\Response\ServerException::class,
                ]
            );

            // Add processors here so they aren't ran unless needed with FingersCrossedHandler
            $handler->pushProcessor($app['logger.processor.env']);
            $handler->pushProcessor($app['logger.processor.request']);

            // Wrap in FingersCrossedHandler for web requests
            if (!self::CLI) {
                $handler = new FingersCrossedHandler($handler, LogLevel::WARNING);
                $handler->setLevel($app['logger.handler.logstash.level']);
            }

            return $handler;
        };

        $app['logger.handler.logstash.level'] = function ($app) {
            $isProd = $app['environment'] === 'production';

            return $isProd ? LogLevel::INFO : LogLevel::DEBUG;
        };

        $app['logger.formatter.logstash'] = function ($app) {
            return new LogstashFormatter($app['app_name']);
        };

        //endregion

        $app['logger.handler.syslog'] = function ($app) {
            return new SyslogHandler($app['app_name'], LOG_USER, LogLevel::ERROR);
        };

        //endregion
    }

    public function subscribe(Container $app, EventDispatcherInterface $dispatcher)
    {
        $dispatcher->addSubscriber($app['logger.listener.http']);
    }
}
