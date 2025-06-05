<?php

declare(strict_types = 1);

namespace Ufo\EventSourcing\Resolver;

class ArrayResolver extends AbstractResolver
{
    public function supportType(mixed $value): bool
    {
        return is_array($value) && array_is_list($value);
    }

    public function isEqual(mixed $oldValue, mixed $newValue): bool
    {
        return $oldValue === $newValue;
    }
}