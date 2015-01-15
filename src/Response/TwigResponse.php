<?php
namespace GMO\Web\Response;

use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Response;

class TwigResponse extends Response
{

    /** @var ParameterBag */
    public $variables;
    /** @var string */
    protected $template;

    /**
     * Constructor.
     *
     * @param string $template  The template name
     * @param array  $variables An array of parameters to pass to the template
     * @param int    $status    The response status code
     * @param array  $headers   An array of response headers
     *
     * @throws \InvalidArgumentException When the HTTP status code is not valid
     */
    public function __construct($template, $variables = array(), $status = 200, $headers = array())
    {
        parent::__construct('', $status, $headers);
        $this->template = $template;
        $this->variables = new ParameterBag($variables);
    }

    /** @return string */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * @param string $template
     */
    public function setTemplate($template)
    {
        $this->template = $template;
    }
}
