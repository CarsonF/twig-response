<?php declare(strict_types=1);

namespace Gmo\Web;

class RequestFactory
{
    protected $options = [];

    /**
     * Constructor.
     *
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $options += [
            'trust_proxies' => true,
        ];
        $this->options = $options;
    }

    /**
     * Registers this as the factory to use when creating requests.
     *
     * @param array $options
     */
    public static function register(array $options = [])
    {
        $factory = new static($options);
        Request::setFactory([$factory, 'createRequest']);
    }

    /**
     * Creates a new request with the given parameters.
     *
     * @param array           $query
     * @param array           $request
     * @param array           $attributes
     * @param array           $cookies
     * @param array           $files
     * @param array           $server
     * @param string|resource $content
     *
     * @return Request
     */
    public function createRequest(
        array $query = [],
        array $request = [],
        array $attributes = [],
        array $cookies = [],
        array $files = [],
        array $server = [],
        $content = null
    ) {
        $request = new Request($query, $request, $attributes, $cookies, $files, $server, $content);

        $this->modifyRequest($request);

        return $request;
    }

    protected function modifyRequest(Request $request): void
    {
        $proxies = $this->options['trust_proxies'];
        if ($proxies === true) {
            $proxies = ['127.0.0.1', $request->server->get('REMOTE_ADDR')];
        }
        if ($proxies !== false) {
            Request::setTrustedProxies((array) $proxies);
        }
    }
}
