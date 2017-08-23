<?php declare(strict_types=1);

namespace Gmo\Web\Provider;

use Gmo\Web\EventListener as Listener;
use Gmo\Web\RequestFactory;
use Gmo\Web\Routing\ControllerCollection;
use Gmo\Web\Routing\LazyUrlGenerator;
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
        $app['controllers_factory'] = function ($app) {
            return new ControllerCollection($app['route_factory']);
        };

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
