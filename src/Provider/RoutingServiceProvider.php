<?php declare(strict_types=1);

namespace Gmo\Web\Provider;

use Gmo\Web\EventListener as Listener;
use Gmo\Web\RequestFactory;
use Gmo\Web\Routing\ControllerCollection;
use Gmo\Web\Routing\LazyUrlGenerator;
use Gmo\Web\Routing\LocaleControllerCollection;
use Gmo\Web\Routing\UrlMatcher;
use Silex\Application;
use Silex\Provider\UrlGeneratorServiceProvider;
use Silex\ServiceProviderInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

class RoutingServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['supported_locales'] = $app->share(function ($app) {
            return [$app['locale']];
        });

        $app['controllers_factory.default'] = function ($app) {
            return new ControllerCollection($app['route_factory']);
        };

        $app['controllers_factory.locale'] = function ($app) {
            return new LocaleControllerCollection($app['route_factory'], $app['supported_locales']);
        };

        // Change to use LocaleControllerCollection if locale routes are enabled.
        $app['controllers_factory'] = function ($app) {
            if ($app['routing.locale_routes'] ?? false) {
                return $app['controllers_factory.locale'];
            }

            return $app['controllers_factory.default'];
        };

        // Main controller should not add locale prefixes because
        // this would force all routes to be locale aware.
        $app['controllers'] = $app->share(function ($app) {
            return $app['controllers_factory.default'];
        });

        $app['url_matcher'] = $app->share(function ($app) {
            return new UrlMatcher($app['routes'], $app['request_context']);
        });

        if (!isset($app['url_generator'])) {
            $app->register(new UrlGeneratorServiceProvider());
        }

        $app['url_generator.lazy'] = $app->share(function ($app) {
            return new LazyUrlGenerator(
                function () use ($app) {
                    return $app['url_generator'];
                }
            );
        });

        $app['routing.listener.request.json'] = $app->share(function ($app) {
            return new Listener\JsonRequestTransformerListener($app['routes']);
        });

        $app['routing.listener.exception.json'] = $app->share(function () {
            return new Listener\ExceptionToJsonListener();
        });

        $app['routing.listener.view.template'] = $app->share(function ($app) {
            return new Listener\TemplateViewListener($app['twig']);
        });

        $app['routing.listener.view.json'] = $app->share(function () {
            return new Listener\JsonViewListener();
        });
    }

    public function boot(Application $app)
    {
        if ($app['request_factory.enabled'] ?? true) {
            RequestFactory::register($app['request_factory.options'] ?? []);
        }
        Request::enableHttpMethodParameterOverride();

        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher = $app['dispatcher'];
        $dispatcher->addSubscriber($app['routing.listener.request.json']);
        $dispatcher->addSubscriber($app['routing.listener.exception.json']);
        $dispatcher->addSubscriber($app['routing.listener.view.template']);
        $dispatcher->addSubscriber($app['routing.listener.view.json']);
    }
}
