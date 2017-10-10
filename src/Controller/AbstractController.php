<?php declare(strict_types=1);

namespace Gmo\Web\Controller;

use Gmo\Web\Response\TemplateView;
use Gmo\Web\Routing\DefaultControllerAwareInterface;
use Psr\Container\ContainerInterface;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Abstract controller class with shortcut methods.
 */
abstract class AbstractController implements ControllerProviderInterface, ContainerInterface
{
    /** @var Application */
    protected $app;

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
    public function connect(Application $app): ControllerCollection
    {
        $this->app = $app;

        $controllers = $this->createControllerCollection($app);

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
     * @param Application $app
     *
     * @return ControllerCollection
     */
    protected function createControllerCollection(Application $app): ControllerCollection
    {
        return $app['controllers_factory'];
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

    /**
     * Shortcut for {@see UrlGeneratorInterface::generate}
     *
     * @param string $name          #Route The name of the route
     * @param array  $params        An array of parameters
     * @param int    $referenceType The type of reference to be generated (one of the constants)
     *
     * @return string
     */
    public function generateUrl(string $name, array $params = [], int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH): string
    {
        return $this->app['url_generator']->generate($name, $params, $referenceType);
    }

    /**
     * Redirects the user to another URL.
     *
     * @param string $url    The URL to redirect to
     * @param int    $status The status code (302 by default)
     *
     * @return RedirectResponse
     */
    public function redirect(string $url, int $status = 302): RedirectResponse
    {
        return new RedirectResponse($url, $status);
    }

    /**
     * Returns a RedirectResponse to the given route with the given parameters.
     *
     * @param string $route      #Route The name of the route
     * @param array  $parameters An array of parameters
     * @param int    $status     The status code to use for the Response
     *
     * @return RedirectResponse
     */
    public function redirectToRoute(string $route, array $parameters = [], int $status = 302): RedirectResponse
    {
        return $this->redirect($this->generateUrl($route, $parameters), $status);
    }

    /**
     * Convert some data into a JSON response.
     *
     * @param mixed $data    The response data
     * @param int   $status  The response status code
     * @param array $headers An array of response headers
     *
     * @return JsonResponse
     */
    public function json($data = [], int $status = 200, array $headers = []): JsonResponse
    {
        return new JsonResponse($data, $status, $headers);
    }

    public function session(): SessionInterface
    {
        return $this->app['session'];
    }

    public function flashes(): FlashBagInterface
    {
        $session = $this->session();
        if (method_exists($session, 'getFlashBag')) {
            return $session->getFlashBag();
        }

        throw new \LogicException('Session must have getFlashBag() method');
    }

    /**
     * Dispatches an event to all registered listeners.
     *
     * @param string $eventName The name of the event to dispatch. The name of the event is the name of the method that
     *                          is invoked on listeners.
     * @param Event  $event     The event to pass to the event handlers/listeners. If not supplied, an empty Event
     *                          instance is created.
     *
     * @return Event
     */
    public function dispatch(string $eventName, Event $event = null): Event
    {
        return $this->app['dispatcher']->dispatch($eventName, $event);
    }

    /**
     * Creates and returns a Form instance from the type of the form.
     *
     * @param string $type    #FormType The built type of the form
     * @param mixed  $data    The initial data for the form
     * @param array  $options Options for the form
     *
     * @return FormInterface
     */
    public function createForm(string $type = FormType::class, $data = null, array $options = []): FormInterface
    {
        return $this->app['form.factory']->create($type, $data, $options);
    }

    /**
     * Returns a form builder.
     *
     * @param string $type    #FormType The type of the form
     * @param mixed  $data    The initial data
     * @param array  $options The options
     *
     * @return FormBuilderInterface The form builder
     */
    public function createFormBuilder(string $type = FormType::class, $data = null, array $options = []): FormBuilderInterface
    {
        return $this->app['form.factory']->createBuilder($type, $data, $options);
    }

    /**
     * Returns a TemplateView.
     *
     * @param string   $template #Template name
     * @param iterable $context  Template context
     *
     * @return TemplateView
     */
    public function render(string $template, iterable $context = []): TemplateView
    {
        return new TemplateView($template, $context);
    }

    /**
     * Shortcut for creating a TemplateView with a form.
     *
     * @param FormInterface $form
     * @param string        $template #Template name
     * @param array         $context  Template context
     *
     * @return TemplateView
     */
    public function renderForm(FormInterface $form, string $template, array $context = []): TemplateView
    {
        $context['form'] = $form->createView();

        return $this->render($template, $context);
    }
}
