<?php

declare(strict_types = 1);

namespace Ufo\EventSourcing\Resolver;

use Ufo\EventSourcing\Contracts\ResolverInterface;
use Ufo\EventSourcing\Exceptions\NoDiffDetectedException;

abstract class AbstractResolver implements ResolverInterface
{
    public function resolve(mixed $oldValue, mixed $newValue, ?ContextDTO $context = null): mixed
    {
        $context ??= ContextDTO::create();
        if (!$context->ignorePreview() && $this->isEqual($oldValue, $newValue))
            throw NoDiffDetectedException::fromPropertyName($context->getPath() ?? ContextDTO::ROOT_PARAM);

        return $newValue;
    }

    abstract public function isEqual(mixed $oldValue, mixed $newValue): bool;
}