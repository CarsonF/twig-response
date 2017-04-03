<?php declare(strict_types=1);

namespace Gmo\Web\Routing;

use Silex\Route;

/**
 * Extends ControllerCollection to automatically:
 * 1) Prefix all routes with locale variable
 * 2) Create a separate "locale-less" route for each route
 *
 * The bound route name is on the route with the locale prefix.
 */
class LocaleControllerCollection extends PrefixedVariableControllerCollection
{
    const SHORT_REGEX = '[a-zA-Z]{2}';
    const LONG_REGEX = '[a-zA-Z]{2}(?:[-_][a-zA-Z]{2})?';

    /**
     * LocaleControllerCollection constructor.
     *
     * @param Route           $defaultRoute
     * @param string|string[] $supportedLocales Regex requirement for locale, or a list of locales
     */
    public function __construct(Route $defaultRoute, $supportedLocales = self::SHORT_REGEX)
    {
        $requirement = is_array($supportedLocales) ? implode('|', $supportedLocales) : $supportedLocales;
        parent::__construct($defaultRoute, $requirement);
    }

    /**
     * {@inheritdoc}
     */
    protected function getVariableName(): string
    {
        return '_locale';
    }
}
