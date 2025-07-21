<?php

declare(strict_types = 1);

namespace Ufo\EventSourcing\Resolver;

use Ufo\EventSourcing\Utils\ArrayHelper;

class ArrayResolver extends AbstractResolver
{
    public function supportType(mixed $value, ?ContextDTO $context = null): bool
    {
        return is_array($value) && !($context?->isCurrentPathAssoc() || ArrayHelper::isAssociative($value));
    }

    public function resolve(mixed $oldValue, mixed $newValue, ?ContextDTO $context = null): mixed
    {
        return array_values(parent::resolve($oldValue, $newValue, $context));
    }

    public function isEqual(mixed $oldValue, mixed $newValue): bool
    {
        return $oldValue === $newValue;
    }
}