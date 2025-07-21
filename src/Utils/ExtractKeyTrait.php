<?php

declare(strict_types = 1);

namespace Ufo\EventSourcing\Utils;

trait ExtractKeyTrait
{
    protected function extractKey(array|object $item, string $keyField): mixed
    {
        if (is_array($item)) {
            return $item[$keyField] ?? null;
        }

        if (is_object($item)) {
            if (method_exists($item, $keyField)) {
                return $item->{$keyField}();
            }

            $getter = 'get' . ucfirst($keyField);
            if (method_exists($item, $getter)) {
                return $item->{$getter}();
            }

            $reflection = new \ReflectionObject($item);
            if (
                $reflection->hasProperty($keyField)
                && ($property = $reflection->getProperty($keyField))
                && $property->isInitialized($item)
            ) {
                return $property->getValue($item);
            }
        }

        return null;
    }
}