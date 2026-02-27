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
use Ufo\EventSourcing\Contracts\ValueNormalizerInterface;
use Ufo\EventSourcing\Exceptions\InvalidObjectException;
use Ufo\EventSourcing\Exceptions\NoDiffDetectedException;

class ObjectResolver extends AbstractResolver
{
    public function __construct(
        protected ResolverInterface&MainResolverInterface $resolver,
        protected ValueNormalizerInterface $valueNormalizer,
    ) {}

    public function supportType(mixed $value, ?ContextDTO $context = null): bool
    {
        return is_object($value);
    }

    /**
     * @throws ReflectionException
     */
    public function resolve(mixed $oldValue, mixed $newValue, ?ContextDTO $context = null): array
    {
        $context ??= ContextDTO::create();
        $path = $context->getPath();

        $this->checkClass($oldValue, $newValue, $context);

        if (!is_null($oldValue) && $this->isEqual($oldValue, $newValue))
            throw NoDiffDetectedException::fromPropertyName($path);

        return $this->collectDiff($oldValue, $newValue, $context);
    }

    public function isEqual(mixed $oldValue, mixed $newValue): bool
    {
        $oldValue = $this->valueNormalizer->normalize($oldValue);
        $newValue = $this->valueNormalizer->normalize($newValue);

        return $oldValue === $newValue;
    }

    /**
     * @throws ReflectionException
     */
    private function collectDiff(?object $oldValue, object $newValue, ContextDTO $context): array
    {
        $diff = [];
        $ref = new \ReflectionObject($newValue);

        if ($context->ignorePreview()) {
            $oldValue = null;
        }

        foreach ($ref->getProperties() as $property) {
            if ($this->shouldIgnore($property)) continue;
            if (!$property->isInitialized($newValue)) throw new InvalidObjectException();

            $nextContext = $this->enrichContextWithCollectionInfo($property, $context, $newValue);
            $newVal = $property->getValue($newValue);

            $hasPropertyInOld = $oldValue && (new \ReflectionClass($oldValue))->hasProperty($property->getName());

            if (!$hasPropertyInOld) {
                $this->resolveValue($newVal, $nextContext, $diff, oldVal: null, ignorePreview: true);
                continue;
            }

            $oldProp = (new \ReflectionClass($oldValue))->getProperty($property->getName());

            if (!$oldProp->isInitialized($oldValue)) {
                $this->resolveValue($newVal, $nextContext, $diff, oldVal: null, ignorePreview: true);
                continue;
            }

            $oldVal = $oldProp->getValue($oldValue);

            $this->resolveValue($newVal, $nextContext, $diff, $oldVal);
        }

        if (empty($diff)) {
            throw NoDiffDetectedException::fromPropertyName($context->getPath());
        }

        return $diff;
    }

    protected function resolveValue(
        mixed $newVal,
        ContextDTO $nextContext,
        array &$diff,
        mixed $oldVal = null,
        bool $ignorePreview = false
    ): void
    {
        $nextContext = $nextContext->withIgnorePreview($ignorePreview);
        try {
            $diff[$nextContext->getParam()] = $this->resolver->resolve($oldVal, $newVal, $nextContext);
        } catch (NoDiffDetectedException) {}
    }

    protected function shouldIgnore(ReflectionProperty $property): bool
    {
        return !empty($property->getAttributes(ChangeIgnore::class, ReflectionAttribute::IS_INSTANCEOF));
    }

    protected function checkClass(?object $oldValue, object $newValue, ContextDTO $context): void
    {
        if (!$context->checkClassEquality) return;

        if (!is_null($oldValue) && get_class($oldValue) !== get_class($newValue))
            throw new \InvalidArgumentException(sprintf(
                'Expected objects of the same class, got %s and %s.', get_class($oldValue), get_class($newValue)
            ));
    }

    protected function enrichContextWithCollectionInfo(ReflectionProperty $property, ContextDTO $context, object $object): ContextDTO
    {
        $nextContext = $context->forPath($property->getName());

        $asCollectionAttributes = $property->getAttributes(AsCollection::class, ReflectionAttribute::IS_INSTANCEOF);
        if (!$asCollectionAttributes) return $nextContext;

        /** @var AsCollection $asCollection */
        $asCollection = $asCollectionAttributes[0]->newInstance();

        if ($property->isInitialized($object)) {
            $value = $property->getValue($object);
            if (is_array($value)) {
                $nextContext = $nextContext->makeAssocByPath('');
            }
        }

        return $nextContext;
    }
}