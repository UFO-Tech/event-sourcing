<?php

declare(strict_types = 1);

namespace Ufo\EventSourcing\Exceptions;

use Throwable;

class NoDiffDetectedException extends \LogicException
{
    public static function fromPropertyName(string $propertyName, int $code = 0, ?Throwable $previous = null): static
    {
        return new static("No diff detected for property $propertyName", $code, $previous);
    }
}