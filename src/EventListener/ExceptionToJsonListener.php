<?php declare(strict_types=1);

namespace Gmo\Web\EventListener;

use Gmo\Common\Str;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Throwable;

/**
 * Converts HTTP exceptions to JSON responses.
 */
class ExceptionToJsonListener implements EventSubscriberInterface
{
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        if (!$this->isApplicable($event->getRequest(), $event)) {
            return;
        }

        $response = $this->convert($event->getException(), $event);

        $event->setResponse($response);
    }

    protected function isApplicable(Request $request, GetResponseForExceptionEvent $event)
    {
        return Str::startsWith($request->getPathInfo(), '/api/', false);
    }

    protected function convert(Throwable $ex, GetResponseForExceptionEvent $event)
    {
        $statusCode = $ex instanceof HttpExceptionInterface ? $ex->getStatusCode() : 500;

        $errorType = Str::removeLast(Str::className($ex), 'Exception');
        $response = new JsonResponse(
            [
                'success'   => false,
                'errorType' => $errorType ?: 'Unknown',
                'code'      => $statusCode,
                'message'   => $ex->getMessage(),
            ]
        );
        $response->setStatusCode($statusCode, $errorType ?: null);

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', -7],
        ];
    }
}
