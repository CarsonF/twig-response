<?php declare(strict_types=1);

namespace Gmo\Web\Provider;

use Bolt\Collection\Arr;
use Bolt\Collection\MutableBag;
use Bolt\Common\Str;
use Gmo\Common\Log\Handler\FallbackHandler;
use Gmo\Common\Log\Handler\RateLimitingHandler;
use Gmo\Common\Log\Processor\ExceptionTraceProcessor;
use Gmo\Web\EventListener\HttpLogListener;
use Gmo\Web\Logger\Formatter\LogstashFormatter;
use Gmo\Web\Logger\Formatter\SlackFormatter;
use Gmo\Web\Logger\Handler\SlackHandler;
use Gmo\Web\Logger\Processor;
use Monolog\Handler\FingersCrossedHandler;
use Monolog\Handler\NullHandler;
use Monolog\Handler\RedisHandler;
use Monolog\Handler\SyslogHandler;
use Predis;
use Psr\Log\LogLevel;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Bridge\Monolog\Handler\DebugHandler;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Bridge\Monolog\Processor\DebugProcessor;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Monolog services.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class LoggerServiceProvider implements ServiceProviderInterface
{
    private const CLI = PHP_SAPI === 'cli';

    public function register(Application $app)
    {
        $app['logger'] = $app->share(function ($app) {
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
        });

        $app['logger.new'] = $app->protect(function ($name) use ($app) {
            return $app['logger']->withName(Str::className($name) ?: $name);
        });

        $app['logger.handlers'] = $app->share(function ($app) {
            $handlers = new MutableBag();

            if (($env = $app['environment']) && ($env === 'production' || $env === 'staging')) {
                $handlers[] = $app['logger.handler.logstash'];
                $handlers[] = $app['logger.handler.slack'];
            }

            return $handlers;
        });

        $app['logger.processors'] = $app->share(function ($app) {
            $processors = new MutableBag();

            $processors[] = new Processor\JsonSerializableProcessor();

            return $processors;
        });

        $app['logger.listener.http'] = $app->share(function ($app) {
            return new HttpLogListener($app['logger']);
        });


        //region Processors

        $app['logger.processor.env'] = $app->share(function ($app) {
            return new Processor\ConstantProcessor('env', $app['environment']);
        });

        $app['logger.processor.exception_trace'] = $app->share(function ($app) {
            return new ExceptionTraceProcessor($app['root_path']);
        });

        $app['logger.processor.host'] = $app->share(function ($app) {
            return new Processor\ConstantProcessor('host', gethostname());
        });

        $app['logger.processor.kibana_request_id'] = $app->share(function ($app) {
            return new Processor\KibanaRequestIdUrlProcessor($app['kibana.host']);
        });

        $app['logger.processor.request'] = $app->share(function ($app) {
            return new Processor\RequestProcessor($app['request_stack']);
        });

        //endregion

        //region Handlers

        $app['logger.redis'] = $app->share(function () {
            return new Predis\Client(['database' => 4]);
        });

        //region Logstash

        $app['logger.handler.logstash'] = $app->share(function ($app) {
            $handler = new RedisHandler(
                $app['logger.redis'],
                $app['logger.handler.logstash.key'] ?? 'logging',
                // Keep everything for web requests since they are filtered down with the
                // FingersCrossedHandler using the `logger.handler.logstash.level` value.
                self::CLI ? $app['logger.handler.logstash.level'] : Logger::DEBUG,
                true,
                $app['logger.handler.logstash.cap_size'] ?? 10000
            );
            $handler->setFormatter($app['logger.formatter.logstash']);

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
        });

        $app['logger.handler.logstash.level'] = $app->share(function ($app) {
            $isProd = $app['environment'] === 'production';

            return $isProd ? LogLevel::INFO : LogLevel::DEBUG;
        });

        $app['logger.formatter.logstash'] = $app->share(function ($app) {
            return new LogstashFormatter($app['app_name']);
        });

        //endregion

        //region Slack

        $app['logger.handler.slack'] = $app->share(function ($app) {
            $slack = new SlackHandler(
                $app['logger.handler.slack.token'],
                $app['logger.handler.slack.channel']
            );
            $slack->setFormatter($app['logger.formatter.slack']);

            $slack->pushProcessor($app['logger.processor.exception_trace']);
            $slack->pushProcessor($app['logger.processor.kibana_request_id']);
            $slack->pushProcessor($app['logger.processor.host']);
            $slack->pushProcessor($app['logger.processor.request']);

            $slack = new RateLimitingHandler($app['logger.redis'], $slack, 60);

            return $slack;
        });

        $app['logger.formatter.slack'] = $app->share(function ($app) {
            return new SlackFormatter();
        });

        //endregion

        $app['logger.handler.syslog'] = $app->share(function ($app) {
            return new SyslogHandler($app['app_name'], LOG_USER, LogLevel::ERROR);
        });

        //endregion

    }

    public function boot(Application $app)
    {
        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher = $app['dispatcher'];
        $dispatcher->addSubscriber($app['logger.listener.http']);
    }
}
