<?php declare(strict_types=1);

namespace Gmo\Web\EventListener;

use Bolt\Collection\Arr;
use Gmo\Web\Routing\LocaleControllerCollection;
use Silex\Application;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RequestContext;

/**
 * Parses locale from request and sets it on the request context, request, and app.
 *
 * This replaces Silex's LocaleListener.
 *
 * Locale is parsed from request before url matching, so that we have the correct
 * locale even if url matching fails. This allows for localized error pages.
 *
 * Locale is determined in this order:
 * - `language` query parameter
 * - First path segment (i.e. `/en/*`)
 * - `Accept-Language` header
 *
 * For all of these cases the locale has to be one of the supported locales given, or the default is used.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class LocaleListener implements EventSubscriberInterface
{
    protected const PATH_REGEX = '#^/(' . LocaleControllerCollection::LONG_REGEX . ')(/.*)?$#';

    /** @var Application */
    protected $app;
    /** @var RequestStack */
    protected $requestStack;
    /** @var RequestContext */
    protected $requestContext;

    /** @var string[] */
    protected $supportedLocales;
    /** @var string */
    protected $defaultLocale;
    /** @var string */
    protected $queryStringKey = 'language';

    /**
     * Constructor.
     *
     * @param Application       $app
     * @param RequestStack      $requestStack
     * @param RequestContext    $context
     * @param iterable|string[] $supportedLocales
     * @param string            $defaultLocale
     */
    public function __construct(
        Application $app,
        RequestStack $requestStack,
        RequestContext $context,
        iterable $supportedLocales,
        string $defaultLocale = 'en'
    ) {
        $this->app = $app;
        $this->requestContext = $context;
        $this->requestStack = $requestStack;
        $this->supportedLocales = Arr::from($supportedLocales);
        $this->defaultLocale = $defaultLocale;
    }

    /**
     * Parses the locale from request and sets it in router context, request, and app.
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event): void
    {
        $request = $event->getRequest();

        $request->setDefaultLocale($this->defaultLocale);

        $locale = $request->getPreferredLanguage($this->supportedLocales);

        $locale = $this->getSupportedLocale($request->getPathInfo(), $locale);

        $locale = $this->getFromQueryString($request, $locale);

        // Needed for url matching
        $this->requestContext->setParameter('_locale', $locale);

        // Needed for error page selection when url matching fails
        $this->app['locale'] = $locale;
        $request->setLocale($locale);
    }

    /**
     * Resets locale in router context to parent request's locale.
     *
     * @param FinishRequestEvent $event
     */
    public function onKernelFinishRequest(FinishRequestEvent $event): void
    {
        if (($parentRequest = $this->requestStack->getParentRequest()) !== null) {
            $this->requestContext->setParameter('_locale', $parentRequest->getLocale());
        }
    }

    /**
     * Returns the locale from the query parameters if it exists and is a valid locale.
     *
     * This locale does not need to be a supported locale.
     *
     * @param Request $request
     * @param string  $default
     *
     * @return string
     */
    public function getFromQueryString(Request $request, string $default): string
    {
        if (!$request->query->has($this->queryStringKey)) {
            return $default;
        }

        $locale = $request->query->getAlpha($this->queryStringKey);

        return in_array($locale, $this->supportedLocales) ? $locale : $default;
    }

    /**
     * Extracts the locale from the path and matches it to the supported locales list.
     * If either of those fail, the default is returned.
     *
     * @param string $path
     * @param string $default
     *
     * @return string
     */
    protected function getSupportedLocale(string $path, string $default): string
    {
        $locale = $this->extractLocale($path);
        if (!$locale) {
            return $default;
        }

        if (in_array($locale, $this->supportedLocales)) {
            return $locale;
        }

        $locale = substr($locale, 0, 2);
        if (in_array($locale, $this->supportedLocales)) {
            return $locale;
        }

        return $default;
    }

    /**
     * Extracts the locale from the given path and normalizes it.
     *
     * Example:
     *
     *       /en/foo    => en
     *       /en-us/foo => en_US
     *
     * @param string $path
     *
     * @return string|null
     */
    protected function extractLocale(string $path): ?string
    {
        if (!preg_match(static::PATH_REGEX, $path, $matches)) {
            return null;
        }

        $locale = strtolower($matches[1]);
        if (strlen($locale) === 5) {
            $locale = substr($locale, 0, 2) . '_' . strtoupper(substr($locale, 3));
        }

        return $locale;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            // Must be higher than RouterListener
            // so locale is set regardless of route matching
            KernelEvents::REQUEST        => ['onKernelRequest', 40],
            KernelEvents::FINISH_REQUEST => 'onKernelFinishRequest',
        ];
    }
}
