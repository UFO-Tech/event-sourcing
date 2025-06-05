<?php

declare(strict_types = 1);

namespace Ufo\EventSourcing\Exceptions;

use InvalidArgumentException;
use Throwable;

class InvalidObjectException extends InvalidArgumentException
{
    public function __construct(
        string $message = 'Invalid argument exceptions',
        int $code = 0,
        ?Throwable $previous = null
    )
    {
        parent::__construct($message, $code, $previous);
    }
}