<?php declare(strict_types=1);

namespace Gmo\Web;

use Gmo\Web\Collection\ParameterBag;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

/**
 * {@inheritdoc}
 *
 * Sub-classing to use our ParameterBag.
 */
class Request extends SymfonyRequest
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

    /**
     * Cast a Symfony Request to our subclass.
     *
     * @param SymfonyRequest $request
     *
     * @return Request
     */
    public static function cast(SymfonyRequest $request): Request
    {
        if ($request instanceof self) {
            return $request;
        }

        return new static(
            $request->query->all(),
            $request->request->all(),
            $request->attributes->all(),
            $request->cookies->all(),
            $request->files->all(),
            $request->server->all(),
            $request->getContent(true)
        );
    }
}
