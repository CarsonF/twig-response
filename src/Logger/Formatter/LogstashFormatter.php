<?php declare(strict_types=1);

namespace Gmo\Web\Logger\Formatter;

use Monolog\Formatter\LogstashFormatter as BaseLogstashFormatter;

class LogstashFormatter extends BaseLogstashFormatter
{
    /**
     * @inheritdoc
     *
     * Changed default $contextPrefix and $version.
     */
    public function __construct(
        string $applicationName,
        string $systemName = null,
        string $extraPrefix = null,
        string $contextPrefix = 'ctxt',
        int $version = self::V1
    ) {
        parent::__construct($applicationName, $systemName, $extraPrefix, $contextPrefix, $version);
    }

    /**
     * {@inheritdoc}
     */
    protected function formatV1(array $record)
    {
        // Set context under its own key, instead of prefixing fields in root.
        // This is already applied to Monolog 2.
        // https://github.com/Seldaek/monolog/pull/976
        $context = $record['context'] ?? [];
        unset($record['context']);
        $message = parent::formatV1($record);

        if (!empty($context)) {
            $message[$this->contextPrefix] = $context;
        }

        return $message;
    }
}
