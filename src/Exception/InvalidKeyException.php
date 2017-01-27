<?php declare(strict_types=1);

namespace Gmo\Web\Exception;

class InvalidKeyException extends InvalidParameterException
{
    public function __construct(string $keyName = 'key', string $message = 'The "%s" provided is invalid', int $statusCode = 442)
    {
        parent::__construct($keyName, $message, $statusCode);
    }
}
