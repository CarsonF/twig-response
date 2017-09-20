<?php declare(strict_types=1);

namespace Gmo\Web\Logger\Processor;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * A Monolog Processor that adds request info.
 */
class RequestProcessor
{
    /** @var RequestStack */
    protected $requestStack;

    /**
     * Constructor.
     *
     * @param RequestStack $requestStack
     */
    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function __invoke(array $record): array
    {
        if (!$request = $this->requestStack->getCurrentRequest()) {
            return $record;
        }

        return $this->modify($record, $request);
    }

    protected function modify(array $record, Request $request): array
    {
        $params = [
            'method'    => $request->getMethod(),
            'host'      => $request->getHost(),
            'path'      => $request->getPathInfo(),
            'query'     => $request->query->all(),
            'userAgent' => $request->server->get('HTTP_USER_AGENT'),
        ];
        if ($request->isMethod('POST') || $request->isMethod('PUT')) {
            $params['body'] = $request->request->all();
        }
        if ($referer = $request->headers->get('referer')) {
            $params['referer'] = $referer;
        }
        if ($id = $request->headers->get('X-Request-Id')) {
            $params['id'] = $id;
        }

        $record['extra']['request'] = $params;

        return $record;
    }
}
