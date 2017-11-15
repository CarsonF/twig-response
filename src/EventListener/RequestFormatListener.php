<?php declare(strict_types=1);

namespace Gmo\Web\EventListener;

use Bolt\Collection\Arr;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Sets the request's format (if not previously set) based on
 * the request's "_formats" attribute or the default formats passed to constructor.
 *
 * Also throws exception if no view has not been converted to a response.
 */
class RequestFormatListener implements EventSubscriberInterface
{
    /** @var array */
    private $defaultFormats;

    /**
     * Constructor.
     *
     * @param iterable $defaultFormats
     */
    public function __construct(iterable $defaultFormats = ['html', 'json'])
    {
        $this->defaultFormats = Arr::from($defaultFormats);
    }

    public function onRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        if ($request->getRequestFormat(null) !== null) {
            return; // Format has already been set.
        }

        $formats = $request->attributes->get('_formats', $this->defaultFormats);
        $format = static::getPreferredFormat($request, $formats);
        if ($format !== null) {
            $request->setRequestFormat($format);
        }
    }

    public function onView(GetResponseForControllerResultEvent $event)
    {
        if ($event->getControllerResult() === null) {
            // continue to throw LogicException via HttpKernel if return statement is missed in controller
            return;
        }

        throw new NotAcceptableHttpException('Controller does not have a view that for the requested format.');
    }

    /**
     * Returns the preferred format.
     *
     * @param Request $request The request
     * @param array   $formats An array of available formats
     *
     * @return string|null
     */
    public static function getPreferredFormat(Request $request, array $formats): ?string
    {
        $accept = $request->getAcceptableContentTypes();
        foreach ($accept as $mimeType) {
            $format = $request->getFormat($mimeType);
            if (in_array($format, $formats, true)) {
                return $format;
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => ['onRequest', 31], // After route matching
            KernelEvents::VIEW => ['onView', -128],
        ];
    }
}
