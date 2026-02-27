<?php

declare(strict_types = 1);

namespace Ufo\EventSourcing\Resolver;

use Ufo\EventSourcing\Contracts\MainResolverInterface;
use Ufo\EventSourcing\Contracts\ResolverInterface;
use Ufo\EventSourcing\Contracts\ValueNormalizerInterface;
use Ufo\EventSourcing\Exceptions\NoDiffDetectedException;
use Ufo\EventSourcing\Utils\ArrayHelper;

class ArrayResolver extends AbstractResolver
{
    public function __construct(
        protected ResolverInterface&MainResolverInterface $resolver,
        protected ValueNormalizerInterface $valueNormalizer
    ) {}

    public function supportType(mixed $value, ?ContextDTO $context = null): bool
    {
        return is_array($value) && !($context?->isCurrentPathAssoc() || ArrayHelper::isAssociative($value));
    }

    public function resolve(mixed $oldValue, mixed $newValue, ?ContextDTO $context = null): array
    {
        $context ??= ContextDTO::create();

        $oldValue ??= [];
        $newValue ??= [];

        if ($context->ignorePreview()) {
            $oldValue = [];
        }

        if (count($oldValue) !== count($newValue)) {
            return array_values($newValue);
        }

        $result = array_map(
            fn($item) => $this->resolver->resolve(null, $item, $context),
            parent::resolve($oldValue, $newValue, $context)
        );

        if (empty($result))
            throw NoDiffDetectedException::fromPropertyName($context->getPath());

        return array_values($result);
    }

    public function isEqual(mixed $oldValue, mixed $newValue): bool
    {
        $oldValue = $this->normalizeArray($oldValue);
        $newValue = $this->normalizeArray($newValue);

        return $oldValue === $newValue;
    }

    protected function normalizeArray(array $value): array
    {
        if (ArrayHelper::isAssociative($value)) {
            asort($value);
        }

        $result = [];
        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $result[$key] = $this->normalizeArray($item);
                continue;
            }
            $result[$key] = $this->valueNormalizer->normalize($item);
        }

        return $result;
    }
}