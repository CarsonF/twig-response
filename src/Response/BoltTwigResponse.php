<?php
namespace Gmo\Web\Response;

use Symfony\Component\HttpFoundation\ParameterBag;

class BoltTwigResponse extends TwigResponse
{
    /** @var ParameterBag */
    public $globals;
    /** @var ParameterBag */
    public $overrides;

    /**
     * Constructor.
     *
     * @param string $template  The template name
     * @param array  $variables An array of parameters to pass to the template
     * @param array  $globals   An array of globals to pass to the template
     * @param array  $overrides An array of variables to override in the Application container
     * @param int    $status    The response status code
     * @param array  $headers   An array of response headers
     *
     * @throws \InvalidArgumentException When the HTTP status code is not valid
     */
    public function __construct(
        $template,
        $variables = array(),
        $globals = array(),
        $overrides = array(),
        $status = 200,
        $headers = array()
    ) {
        parent::__construct($template, $variables, $status, $headers);
        $this->globals = new ParameterBag($globals);
        $this->overrides = new ParameterBag($overrides);
    }
}
