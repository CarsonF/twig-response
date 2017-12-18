<?php

declare(strict_types=1);

namespace Gmo\Web\Logger\Processor;

use Bolt\Collection\Arr;
use Carbon\Carbon;
use Gmo\Common\Assert;

/**
 * @internal
 *
 * Helper to generate urls for for kibana
 */
final class KibanaUrl
{
    private $host;
    private $g = [];
    private $a = [
        //'query' => [
        //    'query_string' => [
        //        'analyze_wildcard' => '!t',
        //        'query' => '*',
        //    ],
        //],
        //'sort' => ['!' => ['@timestamp', 'desc']],
        //'columns' => ['!' => '_source'],
        //'interval' => 'auto',
        //'index' => 'logstash-*',
    ];

    public function __construct(string $host)
    {
        $this->host = $host;
    }

    public function filter(string $key, $value): self
    {
        if (!isset($this->a['filters']['!'])) {
            $this->a['filters'] = ['!' => []];
        }

        $this->a['filters']['!'][] = [
            'query' => [
                'match' => [
                    $key => [
                        'query' => "'$value'",
                        'type' => 'phrase',
                    ],
                ],
            ],
            //'$state' => ['store' => 'appState'],
            //'meta' => [
            //    'alias' => '!n',
            //    'disabled' => '!f',
            //    'index' => 'logstash-*',
            //    'key' => $key,
            //    'negate' => '!f',
            //    'value' => "'$value'",
            //],
        ];

        return $this;
    }

    public function query(string $str = '*'): self
    {
        $this->a['query']['query_string'] = [
            'query' => $str,
            //'analyze_wildcard' => '!t',
        ];

        return $this;
    }

    public function time(Carbon $from, Carbon $to): self
    {
        $this->g = [
            'refreshInterval' => [
                'display' => 'Off',
                'pause' => '!f',
                'value' => 0,
            ],
            'time' => [
                'from' => $from->setTimezone('UTC')->format('Y-m-d\TH:i:s.u\Z'),
                'to'   => $to->setTimezone('UTC')->format('Y-m-d\TH:i:s.u\Z'),
                'mode' => 'absolute',
            ],
        ];

        return $this;
    }

    public function columns(string ...$fieldNames)
    {
        $this->a['columns']['!'] = $fieldNames;

        return $this;
    }

    public function sort(string $sort, string $field = '@timestamp'): self
    {
        Assert::oneOf($sort, ['asc', 'desc']);

        $this->a['sort']['!'] = [$field, $sort];

        return $this;
    }

    public function __toString()
    {
        return "https://$this->host/app/kibana#/discover?_g={$this->formatQuery($this->g)}&_a={$this->formatQuery($this->a)}";
    }

    private function formatQuery($data): string
    {
        if (is_scalar($data)) {
            return (string) $this->escapeIfNeeded($data);
        }

        if (is_array($data) && count($data) === 1 && isset($data['!'])) {
            $str = $this->formatQuery($data['!']);

            return is_array($data['!']) ? "!$str" : "!($str)";
        }

        $list = [];
        if (Arr::isIndexed($data)) {
            foreach ($data as $v) {
                $list[] = $this->formatQuery($v);
            }
        } else {
            ksort($data);
            foreach ($data as $k => $v) {
                $list[] = $this->escapeIfNeeded($k) . ':' . $this->formatQuery($v);
            }
        }

        $str = join(',', $list);
        $str = "($str)";

        return $str;
    }

    private function escapeIfNeeded($val) {
        if (!is_string($val)) {
            return $val;
        }

        return $this->needsEscape($val) ? "'$val'" : $val;
    }

    private function needsEscape(string $str): bool
    {
        return strpos($str, ':') !== false
            || strpos($str, '*') !== false
            || strpos($str, '$') !== false
            || strpos($str, '@') !== false
        ;
    }
}
