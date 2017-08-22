<?php declare(strict_types=1);

namespace Gmo\Web\Logger\Processor;

/**
 * A Monolog processor that adds a constant to the extra fields.
 */
class ConstantProcessor
{
    /** @var string */
    protected $key;
    /** @var mixed */
    protected $value;

    /**
     * Constructor.
     *
     * @param string $key
     * @param mixed $value
     */
    public function __construct($key, $value)
    {
        $this->key = $key;
        $this->value = $value;
    }

    public function __invoke(array $record)
    {
        $record['extra'][$this->key] = $this->value;

        return $record;
    }
}
