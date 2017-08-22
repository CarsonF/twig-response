<?php declare(strict_types=1);

namespace Gmo\Web\EventListener;

use Bolt\Common\Exception\ParseException;
use Bolt\Common\Json;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouteCollection;

/**
 * Parses JSON body to request bag if:
 * - Request's content type is json
 * - Request's route has json=true option. This forces parsing when header isn't set (used to skip
 *   fetch's preflight check). This is optional and depends on RouteCollection to be passed in.
 */
class JsonRequestTransformerListener implements EventSubscriberInterface
{
    /** @var RouteCollection|null */
    protected $routes;

    /**
     * Constructor.
     *
     * @param RouteCollection|null $routes
     */
    public function __construct(RouteCollection $routes = null)
    {
        $this->routes = $routes;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        $content = $request->getContent();

        if (empty($content)) {
            return;
        }

        if (!$this->isJsonRequest($request)) {
            return;
        }

        try {
            $this->transformJsonBody($request);
        } catch (ParseException $e) {
            $response = new Response('Unable to parse request.', 400);
            $event->setResponse($response);
        }
    }

    protected function isJsonRequest(Request $request)
    {
        if ($this->routes) {
            $route = $this->routes->get($request->attributes->get('_route'));
            if ($route && $route->getOption('json')) {
                return true;
            }
        }

        return $request->getContentType() === 'json';
    }

    protected function transformJsonBody(Request $request)
    {
        $data = Json::parse($request->getContent());

        if (is_array($data)) {
            $request->request->replace($data);
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 31], // After route matching
        ];
    }
}
