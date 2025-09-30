<?php

declare(strict_types = 1);

namespace Ufo\EventSourcing\Resolver;

use Ufo\EventSourcing\Contracts\MainResolverInterface;
use Ufo\EventSourcing\Contracts\ResolverInterface;
use Ufo\EventSourcing\Exceptions\NoDiffDetectedException;

final class MainResolver implements ResolverInterface, MainResolverInterface
{
    protected ContextDTO $defaultContext;

    /**
     * @var ResolverInterface[] $resolvers
     */
    public function __construct(
        protected array $resolvers = []
    )
    {
        $this->defaultContext = ContextDTO::create();
    }

    public function addResolver(ResolverInterface $resolver): void
    {
        $this->resolvers[] = $resolver;
    }

    public function resolve(mixed $oldValue, mixed $newValue, ?ContextDTO $context = null): mixed
    {
        $context = $context ?? $this->defaultContext;

        foreach ($this->resolvers as $resolver) {
            if (is_array($newValue) && empty($newValue)) {
                if (!$resolver->supportType($oldValue, $context)) continue;
            } elseif (!$resolver->supportType($newValue, $context)) {
                continue;
            }

            try {
                return $resolver->resolve($oldValue, $newValue, $context);
            } catch (NoDiffDetectedException) {
                continue;
            }
        }
        throw NoDiffDetectedException::fromPropertyName($context->getPath());
    }

    public function supportType(mixed $value, ?ContextDTO $context = null): bool
    {
        return true;
    }
}