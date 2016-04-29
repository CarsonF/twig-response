<?php declare(strict_types=1);

namespace Gmo\Web\Provider;

use Gmo\Web\EventListener\TemplateViewListener;
use Silex\Application;
use Silex\ServiceProviderInterface;

class TemplateViewServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(Application $app)
    {
        $app['template_view.listener'] = $app->share(function ($app) {
            return new TemplateViewListener($app['twig']);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Application $app)
    {
        $app['dispatcher']->addSubscriber($app['template_view.listener']);
    }
}
