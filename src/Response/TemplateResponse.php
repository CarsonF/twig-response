<?php declare(strict_types=1);

namespace Gmo\Web\Response;

use Bolt\Collection\Bag;
use Symfony\Component\HttpFoundation\Response;

/**
 * Template response.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class TemplateResponse extends Response
{
    /** @var string */
    protected $template;
    /** @var Bag */
    protected $context;

    /**
     * Constructor.
     *
     * @param string   $template #Template name
     * @param iterable $context  Template context
     * @param mixed    $content  Rendered template content
     * @param int      $status   The response status code
     * @param array    $headers  An array of response headers
     */
    public function __construct(
        string $template,
        iterable $context = null,
        $content = '',
        int $status = 200,
        array $headers = []
    ) {
        parent::__construct($content, $status, $headers);
        $this->template = $template;
        $this->context = Bag::from($context);
    }

    public function getTemplate(): string
    {
        return $this->template;
    }

    public function getContext(): Bag
    {
        return $this->context;
    }

    /**
     * Don't call directly.
     *
     * @internal
     */
    public function __clone()
    {
        parent::__clone();
        $this->context = clone $this->context;
    }
}
