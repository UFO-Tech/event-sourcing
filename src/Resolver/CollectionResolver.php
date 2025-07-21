<?php

declare(strict_types = 1);

namespace Ufo\EventSourcing\Resolver;

use Ufo\EventSourcing\Contracts\ValueNormalizerInterface;
use Ufo\EventSourcing\Contracts\MainResolverInterface;
use Ufo\EventSourcing\Contracts\ResolverInterface;
use Ufo\EventSourcing\Exceptions\NoDiffDetectedException;
use Ufo\EventSourcing\Utils\ArrayHelper;
use Ufo\EventSourcing\Utils\ExtractKeyTrait;

class CollectionResolver extends AbstractResolver
{
    use ExtractKeyTrait;

    public function __construct(
        protected ResolverInterface&MainResolverInterface $resolver,
        protected ValueNormalizerInterface $valueNormalizer
    ) {}

    public function supportType(mixed $value, ?ContextDTO $context = null): bool
    {
        return is_array($value) && ($context?->isCurrentPathAssoc() || ArrayHelper::isAssociative($value));
    }

    public function resolve(mixed $oldValue, mixed $newValue, ?ContextDTO $context = null): array
    {
        $diff = [];

        $oldValue ??= [];
        $context ??= new ContextDTO();

        $indexKey = ($context->isCurrentPathAssoc() && $keyField = $context->getCurrentPathKeyField())
            ? $keyField : null;

        $isReindexable = $indexKey
            && $this->canReindex($oldValue, $indexKey)
            && $this->canReindex($newValue, $indexKey);

        foreach ($oldValue as $originalKey => $originalValue) {
            $key = $this->getKey($originalValue, $originalKey, $isReindexable ? $indexKey : null, $context);

            if (!array_key_exists($key, $newValue)) {
                if ($key !== $originalKey) {
                    $diff[$key] = [
                        $context->deletePlaceholder => true,
                        $context::ORIGINAL_KEY_FIELD => $originalKey,
                    ];
                } else {
                    $diff[$key] = $context->deletePlaceholder;
                }
                continue;
            }

            try {
                $nextContext = $context->forPath((string) $key);
                $diff[$key] = $this->resolveItem($originalValue, $newValue[$key], $key, $originalKey, $nextContext);
            } catch (NoDiffDetectedException) {}
        }

        foreach ($newValue as $originalKey => $value) {
            $key = $this->getKey($value, $originalKey, $isReindexable ? $indexKey : null, $context);

            if (array_key_exists($originalKey, $oldValue)) continue;
            try {
                $nextContext = $context->forPath((string) $key);
                $diff[$key] = $this->resolveItem(null, $value, $key, $originalKey, $nextContext);
            } catch (NoDiffDetectedException) {}
        }

        if (empty($diff)) throw NoDiffDetectedException::fromPropertyName($context->getPath());

        return $diff;
    }

    protected function resolveItem(
        mixed $oldValue,
        mixed $newValue,
        string|int $key,
        string|int $originalKey,
        ContextDTO $context
    ): mixed
    {
        $nextContext = $context->forPath((string) $key);
        $resolved = $this->resolver->resolve($oldValue, $newValue, $nextContext);

        if ($key !== $originalKey) {
            $resolved[$context::ORIGINAL_KEY_FIELD] = $originalKey;
        }

        return $resolved;
    }

    protected function canReindex(mixed $data, string $keyField, ?ContextDTO $context = null): bool
    {
        if (!is_array($data)) return false;

        foreach ($data as $originalKey => $item) {
            if (!is_array($item) && !is_object($item)) {
                $this->triggerWarning($context, sprintf(
                    'Invalid type for reindex at path "%s[%s]", expected array|object',
                    $context?->getPath() ?? '', $originalKey
                ));
                return false;
            }

            if (!$this->extractKey($item, $keyField)) {
                $this->triggerWarning($context, sprintf(
                    'Missing key "%s" at path "%s[%s]"',
                    $keyField,
                    $context?->getPath() ?? '', $originalKey
                ));
                return false;
            }
        }

        return true;
    }

    protected function getKey(mixed $item, string|int $originalKey, ?string $indexKey, ?ContextDTO $context): string|int
    {
        if (!$indexKey) return $originalKey;

        $key = $this->extractKey($item, $indexKey);

        if ($key === null) {
            $this->triggerWarning($context, sprintf(
                'Missing key "%s" for item at path "%s[%s]"',
                $indexKey,
                $context?->getPath() ?? '', $originalKey
            ));
            return $originalKey;
        }

        return $key;
    }

    public function isEqual(mixed $oldValue, mixed $newValue): bool
    {
        return $oldValue === $newValue;
    }

    protected function triggerWarning(?ContextDTO $context, string $message): void
    {
        if ($context && !$context->isWarningsSuppressed()) {
            trigger_error($message, E_USER_WARNING);
        }
    }
}