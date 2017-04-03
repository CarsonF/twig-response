<?php declare(strict_types=1);

namespace Gmo\Web\EventListener;

use Exception;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Silex\EventListener\LogListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Log request, response and exceptions.
 */
class HttpLogListener extends LogListener implements LoggerAwareInterface
{
    /**
     * {@inheritdoc}
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    protected function logRequest(Request $request)
    {
        $this->logger->info('Received request');
    }

    /**
     * {@inheritdoc}
     */
    protected function logResponse(Response $response)
    {
        if ($response instanceof RedirectResponse) {
            $this->logger->info('Sending redirect', [
                'target' => $response->getTargetUrl(),
                'code'   => $response->getStatusCode(),
            ]);
        } else {
            $this->logger->debug('Sending response', [
                'code' => $response->getStatusCode(),
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function logException(Exception $e)
    {
        if ($e instanceof NotFoundHttpException) {
            $this->logger->warning('No route found');

            return;
        }

        if ($e instanceof MethodNotAllowedHttpException) {
            /** @var \Symfony\Component\Routing\Exception\MethodNotAllowedException $previous */
            $previous = $e->getPrevious();
            $this->logger->warning('Method not allowed', [
                'allowed' => $previous->getAllowedMethods(),
            ]);

            return;
        }

        $message = $e->getMessage();
        $level = LogLevel::CRITICAL;
        $context = [
            'exception' => $e,
        ];
        if ($e instanceof HttpExceptionInterface) {
            $code = $e->getStatusCode();
            $context['statusCode'] = $code;
            if ($code < 500) {
                $level = LogLevel::WARNING;
            }
        }

        $this->logger->log($level, $message, $context);
    }
}
