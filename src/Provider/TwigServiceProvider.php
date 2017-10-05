<?php declare(strict_types=1);

namespace Gmo\Web\Provider;

use Gmo\Web\ContainerUtils;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Twig\Loader\FilesystemLoader;
use Webmozart\PathUtil\Path;

/**
 * Configures Twig service.
 *
 * Paths are defaulted based on convention.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class TwigServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        if (!isset($app['twig'])) {
            $app->register(new \Silex\Provider\TwigServiceProvider());
        }

        // Set twig cache if it has not been provided.
        ContainerUtils::extendParameter($app, 'twig.options', 'twig', function ($options, $app) {
            if (!isset($options['cache'])) {
                $options['cache'] = Path::join($app['root_path'], 'var/cache/twig');
            }

            return $options;
        });

        // Use `root_path` to resolve relative twig paths.
        // Use '%root%/templates' if no twig paths have been set.
        $app['twig.loader.filesystem'] = function ($app) {
            $paths = $app['twig.path'];
            if (!$paths) {
                $paths = ['templates'];
            }

            return new FilesystemLoader($paths, $app['root_path']);
        };
    }
}
