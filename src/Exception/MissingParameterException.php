<?php declare(strict_types=1);

namespace Gmo\Web\Exception;

class MissingParameterException extends InvalidParameterException
{
    public function __construct(string $key, string $message = 'The "%s" parameter is missing', int $statusCode = 451)
    {
        parent::__construct($key, $message, $statusCode);
    }
}
