<?php

declare(strict_types=1);

namespace Gmo\Web\Logger\Formatter;

use Monolog\Formatter\FormatterInterface;

/**
 * Formats the message for Slack
 */
class SlackFormatter implements FormatterInterface
{
    /**
     * {@inheritdoc}
     */
    public function format(array $record)
    {
        if (isset($record['context']['exception'])) {
            $message = $this->renderException($record);

            // Don't try to render exception in fields
            unset($record['context']['exception']);
        } else {
            // Collapse message before fields
            $message = $record['message'] . "\n\n\n\n\n";
        }

        $fields = $this->prepareFields($record);
        $message .= $this->renderFields($fields);

        return $message;
    }

    /**
     * {@inheritdoc}
     */
    public function formatBatch(array $records)
    {
        foreach ($records as $key => $record) {
            $records[$key] = $this->format($record);
        }

        return $records;
    }

    protected function prepareFields(array $record): iterable
    {
        $fields = $record['context'];

        if ($request = $record['extra']['request'] ?? null) {
            $fields['Request ID'] = isset($request['id_url'])
                ? sprintf("<%s|%s>", $request['id_url'], $request['id'])
                : $request['id'];
            $fields['Path'] = sprintf('`%s`', $request['path']);
        }

        if ($worker = $record['extra']['worker'] ?? null) {
            $fields['Worker'] = $worker;
        }

        $fields[] = [
            'Channel' => $record['channel'],
            'Host'    => $record['extra']['host'] ?? null,
        ];

        return $fields;
    }

    protected function renderFields(iterable $fields): string
    {
        $message = '';

        foreach ($fields as $title => $value) {
            if (is_array($value)) {
                $items = [];
                foreach ($value as $key => $val) {
                    if ($val === '' || $val === null) {
                        continue;
                    }
                    $items[] = sprintf("*%s:* %s", $key, $val);
                }

                $value = implode("\t\t\t\t", $items);
                $message .= $value . "\n\n";
            } elseif ($value === '' || $value === null) {
                continue;
            } else {
                $message .= sprintf("*%s:* %s\n\n", $title, $value);
            }
        }

        return $message;
    }

    protected function renderException(array $record)
    {
        /** @var \Throwable $e */
        $e = $record['context']['exception'];

        $message = $record['message'] !== $e->getMessage() ? $record['message'] . "\n": '';

        $es = [];
        do {
            $es[] = $e;
        } while ($e = $e->getPrevious());

        // Try to use short (or full) traces from processor or fallback to the normal traces
        $traces = $record['extra']['short_traces'] ?? $record['extra']['traces'] ?? [];
        if (!$traces) {
            foreach ($es as $e) {
                $traces[] = $e->getTrace();
            }
        }

        return $message . $this->formatException($es, $traces);
    }

    /**
     * @param \Throwable[] $es
     * @param array[]      $traces
     *
     * @return string
     */
    protected function formatException(array $es, array $traces): string
    {
        $out = '';
        foreach ($es as $i => $e) {
            $title = get_class($e) . (($code = $e->getCode()) !== 0 ? " ($code)" : '');
            $title = sprintf("*%s*: %s\n", $title, $e->getMessage());

            // Use 5 line breaks for first exception so slack will collapse the message
            $out .= $i === 0 ? $title . "\n\n\n\n" : 'Caused by ' . $title;

            foreach ($traces[$i] as $j => $frame) {
                if (isset($frame['removed'])) {
                    $out .= sprintf("\t... %s more", $frame['removed']);
                    continue;
                }

                $call = '';
                if ($j > 0) {
                    $call = ($frame['class'] ?? '') . ($frame['type'] ?? '') . ($frame['function'] ?? '') . '()';
                    $call = "at `$call`";
                }

                $source = '';
                if (isset($frame['file'])) {
                    $source = ($frame['file'] ?? 'n/a') . ':' . ($frame['line'] ?? 'n/a');
                    $source = "_{$source}_";
                    $source = $j === 0 ? "in $source" : " in $source";
                }

                $message = "\t" . $call . $source;

                // Break frame into two lines if it is too long to fit on one
                if (mb_strlen($message) > 75) {
                    $message = str_replace(" in ", "\n\t\tin ", $message);
                }

                $out .= $message . "\n";
            }
        }

        $out .= "\n\n\n";

        return $out;
    }
}
