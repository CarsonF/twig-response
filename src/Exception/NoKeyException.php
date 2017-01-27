<?php declare(strict_types=1);

namespace Gmo\Web\Exception;

class NoKeyException extends MissingParameterException
{
    public function __construct(string $keyName = 'key', string $message = 'No "%s" was provided', int $statusCode = 441)
    {
        parent::__construct($keyName, $message, $statusCode);
    }
}
