<?php

declare(strict_types = 1);

namespace Ufo\EventSourcing\Resolver;

use Ufo\EventSourcing\Contracts\MainResolverInterface;
use Ufo\EventSourcing\Contracts\ResolverInterface;
use Ufo\EventSourcing\Utils\ArrayHelper;

class ArrayResolver extends AbstractResolver
{
    public function __construct(
        protected ResolverInterface&MainResolverInterface $resolver
    ) {}

    public function supportType(mixed $value, ?ContextDTO $context = null): bool
    {
        return is_array($value) && !($context?->isCurrentPathAssoc() || ArrayHelper::isAssociative($value));
    }

    public function resolve(mixed $oldValue, mixed $newValue, ?ContextDTO $context = null): mixed
    {
        $context ??= ContextDTO::create();

        $result = array_map(
            fn($item) => $this->resolver->resolve(null, $item, $context),
            parent::resolve($oldValue, $newValue, $context)
        );

        return array_values($result);
    }

    public function isEqual(mixed $oldValue, mixed $newValue): bool
    {
        return $oldValue === $newValue;
    }
}