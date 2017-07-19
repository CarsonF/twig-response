<?php declare(strict_types=1);

namespace Gmo\Web\Provider;

use Gmo\Web\EventListener\TemplateViewListener;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Api\EventListenerProviderInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class TemplateViewServiceProvider implements ServiceProviderInterface, EventListenerProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(Container $app)
    {
        $app['template_view.listener'] = function ($app) {
            return new TemplateViewListener($app['twig']);
        };
    }

    /**
     * {@inheritdoc}
     */
    public function subscribe(Container $app, EventDispatcherInterface $dispatcher)
    {
        $dispatcher->addSubscriber($app['template_view.listener']);
    }
}
