<?php declare(strict_types=1);

namespace Gmo\Web\Response;

use Bolt\Collection\MutableBag;

/**
 * A view that will be rendered with Twig and converted to a response.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class TemplateView
{
    /** @var string */
    protected $template;
    /** @var MutableBag */
    protected $context;

    /**
     * Constructor.
     *
     * @param string   $template #Template name
     * @param iterable $context  Template context
     */
    public function __construct(string $template, iterable $context = null)
    {
        $this->setTemplate($template);
        $this->setContext($context);
    }

    public function getTemplate(): string
    {
        return $this->template;
    }

    public function setTemplate(string $template): TemplateView
    {
        $this->template = $template;

        return $this;
    }

    public function getContext(): MutableBag
    {
        return $this->context;
    }

    public function setContext(?iterable $context): TemplateView
    {
        $this->context = new MutableBag($context);

        return $this;
    }

    /**
     * Don't call directly.
     *
     * @internal
     */
    public function __clone()
    {
        $this->context = clone $this->context;
    }
}
