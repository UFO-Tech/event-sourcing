<?php

declare(strict_types = 1);

namespace Ufo\EventSourcing\Resolver;

class ScalarResolver extends AbstractResolver
{
    public function supportType(mixed $value, ?ContextDTO $context = null): bool
    {
        return is_scalar($value);
    }

    public function isEqual(mixed $oldValue, mixed $newValue): bool
    {
        return $oldValue === $newValue;
    }
}