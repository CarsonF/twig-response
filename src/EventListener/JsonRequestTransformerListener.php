<?php declare(strict_types=1);

namespace Gmo\Web\EventListener;

use Bolt\Common\Exception\ParseException;
use Bolt\Common\Json;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Parse JSON body to Request's request bag.
 *
 * The transformation is done when the Request has a body and
 * either the format of the Content-Type header is "json" (see get/setFormat)
 * or the request format is "json" (via setRequestFormat() or via "_format" attribute).
 */
class JsonRequestTransformerListener implements EventSubscriberInterface
{
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
        return $request->getContentType() === 'json' || $request->getRequestFormat() === 'json';
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
