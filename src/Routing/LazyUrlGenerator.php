<?php declare(strict_types=1);

namespace Gmo\Web\Routing;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;

/**
 * Implements a lazy UrlGenerator.
 * Similar concept with {@see \Silex\LazyUrlMatcher LazyUrlMatcher} and
 * {@see \Symfony\Component\HttpKernel\EventListener\RouterListener RouterListener}
 */
class LazyUrlGenerator implements UrlGeneratorInterface
{
    /** @var callable */
    private $factory;
    /** @var UrlGeneratorInterface|null */
    private $urlGenerator;

    /**
     * Constructor.
     *
     * @param callable $factory
     */
    public function __construct(callable $factory)
    {
        $this->factory = $factory;
    }

    /**
     * {@inheritdoc}
     */
    public function setContext(RequestContext $context)
    {
        $this->getUrlGenerator()->setContext($context);
    }

    /**
     * {@inheritdoc}
     */
    public function getContext()
    {
        return $this->getUrlGenerator()->getContext();
    }

    /**
     * {@inheritdoc}
     */
    public function generate($name, $parameters = [], $referenceType = self::ABSOLUTE_PATH)
    {
        return $this->getUrlGenerator()->generate($name, $parameters, $referenceType);
    }

    /**
     * Forward unknown calls to inner UrlGenerator.
     *
     * @param string $method
     * @param array  $args
     *
     * @return mixed
     */
    public function __call(string $method, array $args)
    {
        return call_user_func_array([$this->getUrlGenerator(), 'method'], $args);
    }

    protected function getUrlGenerator(): UrlGeneratorInterface
    {
        if ($this->urlGenerator === null) {
            $urlGenerator = call_user_func($this->factory);
            if (!$urlGenerator instanceof UrlGeneratorInterface) {
                throw new \LogicException(
                    'Factory supplied to LazyUrlGenerator must return implementation of UrlGeneratorInterface.'
                );
            }
            $this->urlGenerator = $urlGenerator;
        }

        return $this->urlGenerator;
    }
}
