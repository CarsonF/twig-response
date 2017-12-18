<?php

declare(strict_types=1);

namespace Gmo\Web\Logger\Processor;

use Carbon\Carbon;

/**
 * Sets the Request ID url to a kibana search url.
 *
 * This depends on the Logstash handler being configured.
 */
class KibanaRequestIdUrlProcessor
{
    /** @var string */
    private $host;
    /** @var string */
    private $logstashRequestIdKey;

    /**
     * Constructor.
     *
     * @param string $host
     * @param string $logstashRequestIdKey
     */
    public function __construct(string $host, string $logstashRequestIdKey = 'request.id')
    {
        $this->host = $host;
        $this->logstashRequestIdKey = $logstashRequestIdKey;
    }

    public function __invoke(array $record): array
    {
        if ($id = $record['extra']['request']['id'] ?? '') {
            $record['extra']['request']['id_url'] = (string) $this->getUrl($id, $record);
        }

        return $record;
    }

    protected function getUrl(string $id, array $record)
    {
        $url = (new KibanaUrl($this->host))
            ->query("$this->logstashRequestIdKey: $id")
        ;

        // Limit to within a couple hours of the request
        $dt = Carbon::instance($record['datetime']);
        $url->time(
            $dt->setTime($dt->hour - 1, 0, 0),
            $dt->setTime($dt->hour + 1, 0, 0)
        );

        return $url;
    }
}
