<?php

declare(strict_types = 1);

namespace Ufo\EventSourcing\Resolver;

use Ufo\EventSourcing\Attributes\ChangeIgnore;
use Ufo\EventSourcing\Contracts\MainResolverInterface;
use Ufo\EventSourcing\Contracts\ResolverInterface;
use Ufo\EventSourcing\Exceptions\InvalidObjectException;
use Ufo\EventSourcing\Exceptions\NoDiffDetectedException;

class ObjectResolver extends AbstractResolver
{
    public function __construct(
        protected ResolverInterface&MainResolverInterface $resolver
    ) {}

    public function supportType(mixed $value): bool
    {
        return is_object($value);
    }

    public function resolve(mixed $oldValue, mixed $newValue, string $paramName = self::DEFAULT_PARAM_NAME): mixed
    {
        if ($this->isEqual($oldValue, $newValue)) throw NoDiffDetectedException::fromPropertyName($paramName);

        if (get_class($oldValue) !== get_class($newValue)) {
            throw new \InvalidArgumentException(sprintf(
                'Expected objects of the same class, got %s and %s.',
                get_class($oldValue),
                get_class($newValue)
            ));
        }

        $diff = [];

        $ref = new \ReflectionObject($oldValue);

        foreach ($ref->getProperties() as $property) {
            if (!$property->isInitialized($newValue) && !$property->isInitialized($oldValue)) continue;

            if (!empty($property->getAttributes(ChangeIgnore::class, \ReflectionAttribute::IS_INSTANCEOF))) continue;

            if (!$property->isInitialized($oldValue)) {
                $diff[$property->getName()] = $property->getValue($newValue);
                continue;
            }

            if (!$property->isInitialized($newValue)) {
                throw new InvalidObjectException();
            }

            $oldValueVal = $property->getValue($oldValue);
            $newVal = $property->getValue($newValue);

            try {
                $diff[$property->getName()] = $this->resolver->resolve($oldValueVal, $newVal, $property->getName());
            } catch (NoDiffDetectedException) {}
        }

        return $diff;
    }

    public function isEqual(mixed $oldValue, mixed $newValue): bool
    {
        return md5(serialize($oldValue)) === md5(serialize($newValue));
    }
}