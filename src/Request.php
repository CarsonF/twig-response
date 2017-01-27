<?php declare(strict_types=1);

namespace Gmo\Web;

use Gmo\Web\Collection\ParameterBag;
use Symfony\Component\HttpFoundation\Request as RequestBase;

/**
 * {@inheritdoc}
 *
 * Sub-classing to use our ParameterBag.
 */
class Request extends RequestBase
{
    /**
     * Query string parameters ($_GET).
     *
     * @var ParameterBag
     */
    public $query;

    /**
     * Request body parameters ($_POST).
     *
     * @var ParameterBag
     */
    public $request;

    /**
     * {@inheritdoc}
     */
    public function initialize(
        array $query = [],
        array $request = [],
        array $attributes = [],
        array $cookies = [],
        array $files = [],
        array $server = [],
        $content = null
    ) {
        parent::initialize($query, $request, $attributes, $cookies, $files, $server, $content);

        $this->request = new ParameterBag($request);
        $this->query = new ParameterBag($query);
    }

    /**
     * {@inheritdoc}
     */
    public static function createFromGlobals()
    {
        $request = parent::createFromGlobals();

        if (!$request->request instanceof ParameterBag) {
            $request->request = new ParameterBag($request->request->all());
        }

        return $request;
    }
}
