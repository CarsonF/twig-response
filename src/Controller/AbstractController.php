<?php declare(strict_types=1);

namespace Gmo\Web\Controller;

use Gmo\Web\Routing\DefaultControllerAwareInterface;
use Psr\Container\ContainerInterface;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;

/**
 * Abstract controller class with shortcut methods.
 */
abstract class AbstractController implements ControllerProviderInterface, ContainerInterface
{
    use ShortcutsTrait;

    /** @var Application */
    private $app;

    /**
     * Define the routes/controllers for this collection.
     *
     * @param ControllerCollection $controllers
     *
     * @return void|ControllerCollection
     */
    abstract protected function defineRoutes(ControllerCollection $controllers);

    /**
     * {@inheritdoc}
     */
    final public function connect(Application $app): ControllerCollection
    {
        $this->app = $app;

        $controllers = $this->createControllerCollection($app['container']);

        if ($controllers instanceof DefaultControllerAwareInterface) {
            $controllers->setDefaultControllerClass($this);
        }

        $new = $this->defineRoutes($controllers);
        if ($new instanceof ControllerCollection) {
            $controllers = $new;
        }

        return $controllers;
    }

    /**
     * Create and return a new controller collection.
     *
     * @param ContainerInterface $container
     *
     * @return ControllerCollection
     */
    protected function createControllerCollection(ContainerInterface $container): ControllerCollection
    {
        return $container->get('controllers_factory');
    }

    /**
     * {@inheritdoc}
     */
    public function get($id)
    {
        return $this->app['container']->get($id);
    }

    /**
     * {@inheritdoc}
     */
    public function has($id)
    {
        return $this->app['container']->has($id);
    }
}
