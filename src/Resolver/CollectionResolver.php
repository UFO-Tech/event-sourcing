<?php

declare(strict_types = 1);

namespace Ufo\EventSourcing\Resolver;

use Ufo\EventSourcing\Contracts\MainResolverInterface;
use Ufo\EventSourcing\Contracts\ResolverInterface;
use Ufo\EventSourcing\Exceptions\NoDiffDetectedException;
use Ufo\EventSourcing\Utils\ArrayHelper;

class CollectionResolver extends AbstractResolver
{
    public function __construct(
        protected ResolverInterface&MainResolverInterface $resolver
    ) {}

    public function supportType(mixed $value, ?ContextDTO $context = null): bool
    {
        return is_array($value) && ($context?->isCurrentPathAssoc() || ArrayHelper::isAssociative($value));
    }

    public function resolve(mixed $oldValue, mixed $newValue, ?ContextDTO $context = null): array
    {
        $diff = [];

        $oldValue ??= [];
        $context ??= ContextDTO::create();

        if ($context->ignorePreview()) {
            $oldValue = [];
        }

        foreach ($oldValue as $originalKey => $originalValue) {
            if ($originalValue === $context->deletePlaceholder) $originalValue = null;

            if (!array_key_exists($originalKey, $newValue)) {
                $diff[$originalKey] = $context->deletePlaceholder;
                continue;
            }

            try {
                $diff[$originalKey] = $this->resolveItem($originalValue, $newValue[$originalKey], $originalKey, $context);
            } catch (NoDiffDetectedException) {}
        }

        foreach ($newValue as $originalKey => $value) {
            if (array_key_exists($originalKey, $oldValue)) continue;
            try {
                $diff[$originalKey] = $this->resolveItem(null, $value, $originalKey, $context, ignorePreview: true);
            } catch (NoDiffDetectedException) {}
        }

        if (empty($diff)) throw NoDiffDetectedException::fromPropertyName($context->getPath());

        return $diff;
    }

    protected function resolveItem(
        mixed $oldValue,
        mixed $newValue,
        string|int $key,
        ContextDTO $context,
        bool $ignorePreview = false
    ): mixed
    {
        $nextContext = $context->forPath((string) $key)->withIgnorePreview($ignorePreview);
        return $this->resolver->resolve($oldValue, $newValue, $nextContext);
    }

    public function isEqual(mixed $oldValue, mixed $newValue): bool
    {
        return $oldValue === $newValue;
    }
}