<?php

declare(strict_types = 1);

namespace Ufo\EventSourcing\Resolver;

use Ufo\EventSourcing\Contracts\ValueNormalizerInterface;
use Ufo\EventSourcing\Contracts\MainResolverInterface;
use Ufo\EventSourcing\Contracts\ResolverInterface;
use Ufo\EventSourcing\Exceptions\NoDiffDetectedException;

class CollectionResolver extends AbstractResolver
{
    public function __construct(
        protected ResolverInterface&MainResolverInterface $resolver,
        protected ValueNormalizerInterface $valueNormalizer
    ) {}

    public function supportType(mixed $value): bool
    {
        return is_array($value) && !array_is_list($value);
    }

    public function resolve(mixed $oldValue, mixed $newValue, string $paramName = self::DEFAULT_PARAM_NAME): array
    {
        $diff = [];

        foreach ($oldValue as $key => $originalValue) {
            if (!array_key_exists($key, $newValue)) {
                $diff[$key] = static::DELETE_FLAG;
                continue;
            }

            try {
                $diff[$key] = $this->resolver->resolve($originalValue, $newValue[$key], $key);
            } catch (NoDiffDetectedException) {}
        }

        foreach ($newValue as $key => $value) {
            if (array_key_exists($key, $oldValue)) continue;
            $diff[$key] = $this->valueNormalizer->normalize($value);
        }

        if (empty($diff)) throw NoDiffDetectedException::fromPropertyName($paramName);

        return $diff;
    }

    public function isEqual(mixed $oldValue, mixed $newValue): bool
    {
        return $oldValue === $newValue;
    }
}