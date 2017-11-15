<?php declare(strict_types=1);

namespace Gmo\Web;

use Symfony\Component\HttpKernel\Kernel;

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
            'trusted_proxies' => true,
            'trusted_headers' => Kernel::MAJOR_VERSION >= 3 ? Request::HEADER_X_FORWARDED_AWS_ELB : -1,
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
        $proxies = $this->options['trusted_proxies'];
        if ($proxies === true) {
            // trust *all* requests
            $proxies = ['127.0.0.1', $request->server->get('REMOTE_ADDR')];
        }
        if ($proxies !== false) {
            $args = [(array) $proxies];
            if (Kernel::MAJOR_VERSION >= 3) {
                $args[] = $this->options['trusted_headers'];
            }
            Request::setTrustedProxies(...$args);
        }
    }
}
