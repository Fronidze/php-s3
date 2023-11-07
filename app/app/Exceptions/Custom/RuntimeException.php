<?php

namespace App\Exceptions\Custom;

use Symfony\Component\HttpKernel\Exception\HttpException;

class RuntimeException extends HttpException
{
    public function __construct(string $message = '', \Throwable $previous = null, array $headers = [], int $code = 0)
    {
        parent::__construct(500, $message, $previous, $headers, $code);
    }

}
