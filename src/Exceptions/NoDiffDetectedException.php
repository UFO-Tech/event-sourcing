<?php

declare(strict_types = 1);

namespace Ufo\EventSourcing\Exceptions;

use Throwable;
use Ufo\EventSourcing\Resolver\ContextDTO;

class NoDiffDetectedException extends \LogicException
{
    public static function fromPropertyName(string $propertyName, int $code = 0, ?Throwable $previous = null): static
    {
        return new static("No diff detected for property $propertyName", $code, $previous);
    }

    public static function fromCurrentContext(?ContextDTO $contextDTO, int $code = 0, ?Throwable $previous = null): static
    {
        return new static('No diff detected for property ' . $contextDTO?->getPath(), $code, $previous);
    }
}