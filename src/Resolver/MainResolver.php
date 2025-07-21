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
        $this->defaultContext = new ContextDTO();
    }

    public function addResolver(ResolverInterface $resolver): void
    {
        $this->resolvers[] = $resolver;
    }

    public function resolve(mixed $oldValue, mixed $newValue, ?ContextDTO $context = null): mixed
    {
        $context = $context ?? $this->defaultContext;

        foreach ($this->resolvers as $resolver) {
            if (!$resolver->supportType($newValue, $context)) continue;
            try {
                return $resolver->resolve($oldValue, $newValue, $context);
            } catch (NoDiffDetectedException) {
                continue;
            }
        }
        throw NoDiffDetectedException::fromPropertyName($context->param);
    }

    public function supportType(mixed $value): bool
    {
        return true;
    }
}