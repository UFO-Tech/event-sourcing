<?php

declare(strict_types = 1);

namespace Ufo\EventSourcing\Resolver;

use Ufo\EventSourcing\Contracts\ResolverInterface;
use Ufo\EventSourcing\Exceptions\NoDiffDetectedException;

abstract class AbstractResolver implements ResolverInterface
{
    public function resolve(mixed $oldValue, mixed $newValue, string $paramName = self::DEFAULT_PARAM_NAME): mixed
    {
        if ($this->isEqual($oldValue, $newValue)) throw NoDiffDetectedException::fromPropertyName($paramName);
        return $newValue;
    }

    abstract public function isEqual(mixed $oldValue, mixed $newValue): bool;
}