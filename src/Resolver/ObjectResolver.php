<?php

declare(strict_types = 1);

namespace Ufo\EventSourcing\Resolver;

use ReflectionAttribute;
use ReflectionException;
use ReflectionProperty;
use Ufo\EventSourcing\Attributes\AsCollection;
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

    /**
     * @throws ReflectionException
     */
    public function resolve(mixed $oldValue, mixed $newValue, ?ContextDTO $context = null): array
    {
        $context ??= new ContextDTO();
        $path = $context->getPath();

        $this->checkClass($oldValue, $newValue);

        if (!is_null($oldValue) && $this->isEqual($oldValue, $newValue))
            throw NoDiffDetectedException::fromPropertyName($path);

        return $this->collectDiff($oldValue, $newValue, $context);
    }

    public function isEqual(mixed $oldValue, mixed $newValue): bool
    {
        return false;
    }

    /**
     * @throws ReflectionException
     */
    private function collectDiff(?object $oldValue, object $newValue, ContextDTO $context): array
    {
        $diff = [];

        try {
            $this->checkClass($oldValue, $newValue);
        } catch (InvalidObjectException) {
            $oldValue = null;
        }

        $ref = new \ReflectionObject($newValue);

        foreach ($ref->getProperties() as $property) {
            if ($this->shouldIgnore($property)) continue;
            if (!$property->isInitialized($newValue)) throw new InvalidObjectException();

            $nextContext = $this->enrichContextWithCollectionInfo($property, $context, $newValue);

            $newVal = $property->getValue($newValue);

            if (!$oldValue || !$property->isInitialized($oldValue)) {
                $diff[$property->getName()] = $this->resolver->resolve(null, $newVal, $nextContext);;
                continue;
            }

            $oldVal = $property->getValue($oldValue);

            try {
                $diffValue = $this->resolver->resolve($oldVal, $newVal, $nextContext);
                $diff[$property->getName()] = $diffValue;
            } catch (NoDiffDetectedException) {}
        }

        if (empty($diff)) throw NoDiffDetectedException::fromPropertyName($context->getPath());

        return $diff;
    }

    protected function shouldIgnore(ReflectionProperty $property): bool
    {
        return !empty($property->getAttributes(ChangeIgnore::class, ReflectionAttribute::IS_INSTANCEOF));
    }

    protected function checkClass(?object $oldValue, object $newValue): void
    {
        if (!is_null($oldValue) && get_class($oldValue) !== get_class($newValue))
            throw new \InvalidArgumentException(sprintf(
                'Expected objects of the same class, got %s and %s.', get_class($oldValue), get_class($newValue)
            ));
    }

    /**
     * @throws ReflectionException
     */
    protected function enrichContextWithCollectionInfo(ReflectionProperty $property, ContextDTO $context, object $object): ContextDTO
    {
        $nextContext = $context->forPath($property->getName());

        $asCollectionAttributes = $property->getAttributes(AsCollection::class, ReflectionAttribute::IS_INSTANCEOF);
        if (!$asCollectionAttributes) return $nextContext;

        /** @var AsCollection $asCollection */
        $asCollection = $asCollectionAttributes[0]->newInstance();

        if ($property->isInitialized($object)) {
            $value = $property->getValue($object);
            if (is_array($value) && $asCollection->keyField) {
                $nextContext = $nextContext->makeAssocByPath('', $asCollection->keyField);
            }
        }

        return $nextContext;
    }
}