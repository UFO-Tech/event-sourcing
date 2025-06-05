<?php

declare(strict_types = 1);

namespace Ufo\EventSourcing\Resolver;

use Ufo\EventSourcing\Contracts\MainResolverInterface;
use Ufo\EventSourcing\Contracts\ResolverInterface;
use Ufo\EventSourcing\Exceptions\NoDiffDetectedException;

final class MainResolver implements ResolverInterface, MainResolverInterface
{
    /**
     * @var ResolverInterface[] $resolvers
     */
    public function __construct(
        protected array $resolvers = []
    ) {}

    public function addResolver(ResolverInterface $resolver): void
    {
        $this->resolvers[] = $resolver;
    }

    public function resolve(mixed $oldValue, mixed $newValue, string $paramName = self::DEFAULT_PARAM_NAME): mixed
    {
        foreach ($this->resolvers as $resolver) {
            if (!$resolver->supportType($newValue)) continue;
            try {
                return $resolver->resolve($oldValue, $newValue, $paramName);
            } catch (NoDiffDetectedException) {
                continue;
            }
        }
        throw NoDiffDetectedException::fromPropertyName($paramName);
    }

    public function supportType(mixed $value): bool
    {
        return true;
    }
}