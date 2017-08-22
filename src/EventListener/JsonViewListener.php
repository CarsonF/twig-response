<?php declare(strict_types=1);

namespace Gmo\Web\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Converts controller results that are iterables to JsonResponses.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class JsonViewListener implements EventSubscriberInterface
{
    /**
     * If controller result is an iterable, convert it to a Response.
     *
     * @param GetResponseForControllerResultEvent $event
     */
    public function onView(GetResponseForControllerResultEvent $event)
    {
        $result = $event->getControllerResult();

        if (!is_iterable($result)) {
            return;
        }

        $response = $this->render($result);

        $event->setResponse($response);
    }

    /**
     * Render TemplateView to a TemplateResponse.
     *
     * @param iterable $view
     *
     * @return Response
     */
    public function render(iterable $view): Response
    {
        if ($view instanceof \Traversable) {
            $view = iterator_to_array($view);
        }

        $view['success'] = true;

        $response = new JsonResponse($view);

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => 'onView',
        ];
    }
}
