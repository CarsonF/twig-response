<?php declare(strict_types=1);

namespace Gmo\Web\Provider;

use Gmo\Web\EventListener as Listener;
use Gmo\Web\RequestFactory;
use Gmo\Web\Routing\ControllerCollection;
use Gmo\Web\Routing\LazyUrlGenerator;
use Gmo\Web\Routing\LocaleControllerCollection;
use Gmo\Web\Routing\Route;
use Gmo\Web\Routing\UrlMatcher;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Api\BootableProviderInterface;
use Silex\Api\EventListenerProviderInterface;
use Silex\Application;
use Silex\Provider\LocaleServiceProvider;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

class RoutingServiceProvider implements ServiceProviderInterface, BootableProviderInterface, EventListenerProviderInterface
{
    public function register(Container $app)
    {
        $app['supported_locales'] = function ($app) {
            return [$app['locale']];
        };

        $app['route_class'] = Route::class;

        $app['controllers_factory.default'] = $app->factory(function ($app) {
            return new ControllerCollection($app['route_factory']);
        });

        $app['controllers_factory.locale'] = $app->factory(function ($app) {
            return new LocaleControllerCollection($app['route_factory'], $app['supported_locales']);
        });

        // Change to use LocaleControllerCollection if locale routes are enabled.
        $app['controllers_factory'] = $app->factory(function ($app) {
            if ($app['routing.locale_routes'] ?? false) {
                return $app['controllers_factory.locale'];
            }

            return $app['controllers_factory.default'];
        });

        // Main controller should not add locale prefixes because
        // this would force all routes to be locale aware.
        $app['controllers'] = function ($app) {
            return $app['controllers_factory.default'];
        };

        $app['url_matcher'] = function ($app) {
            return new UrlMatcher($app['routes'], $app['request_context']);
        };

        $app['url_generator.lazy'] = function ($app) {
            return new LazyUrlGenerator(
                function () use ($app) {
                    return $app['url_generator'];
                }
            );
        };

        // Register LocaleSP if needed because it will subscribe the listener.
        // If we subscribe it we don't know if we are duplicating the subscription.
        if (!isset($app['locale.listener'])) {
            $app->register(new LocaleServiceProvider());
        }

        // Replaces built-in service
        $app['locale.listener'] = function ($app) {
            return new Listener\LocaleListener(
                $app,
                $app['request_stack'],
                $app['request_context'],
                $app['supported_locales'],
                $app['locale']
            );
        };

        $app['routing.listener.request_id'] = function () {
            return new Listener\RequestIdListener();
        };

        $app['routing.listener.request.json'] = function ($app) {
            return new Listener\JsonRequestTransformerListener($app['routes']);
        };

        $app['routing.listener.exception.json'] = function () {
            return new Listener\ExceptionToJsonListener();
        };

        $app['routing.listener.view.template'] = function ($app) {
            if (!isset($app['twig'])) {
                return new Listener\NullListener();
            }

            return new Listener\TemplateViewListener($app['twig.lazy'] ?? $app['twig']);
        };

        $app['routing.listener.view.json'] = function () {
            return new Listener\JsonViewListener();
        };
    }

    public function boot(Application $app)
    {
        if ($app['request_factory.enabled'] ?? true) {
            RequestFactory::register($app['request_factory.options'] ?? []);
        }
        Request::enableHttpMethodParameterOverride();
    }

    public function subscribe(Container $app, EventDispatcherInterface $dispatcher)
    {
        $dispatcher->addSubscriber($app['routing.listener.request_id']);
        $dispatcher->addSubscriber($app['routing.listener.request.json']);
        $dispatcher->addSubscriber($app['routing.listener.exception.json']);
        $dispatcher->addSubscriber($app['routing.listener.view.template']);
        $dispatcher->addSubscriber($app['routing.listener.view.json']);
    }
}
