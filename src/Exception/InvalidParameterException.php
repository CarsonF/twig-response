<?php declare(strict_types=1);

namespace Gmo\Web\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class InvalidParameterException extends HttpException
{
    public function __construct(string $key, string $message = 'The "%s" parameter is invalid', int $statusCode = 452)
    {
        parent::__construct($statusCode, sprintf($message, $key));
    }
}
