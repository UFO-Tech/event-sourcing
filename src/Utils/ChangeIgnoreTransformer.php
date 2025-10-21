<?php

declare(strict_types = 1);

namespace Ufo\EventSourcing\Utils;

use ReflectionAttribute;
use ReflectionClass;
use Ufo\DTO\DTOTransformer;
use Ufo\DTO\Helpers\TypeHintResolver;
use Ufo\EventSourcing\Attributes\ChangeIgnore;

class ChangeIgnoreTransformer extends DTOTransformer
{
    public static function toArray(object $dto, array $renameKey = [], bool $asSmartArray = true): array
    {
        $reflection = new ReflectionClass($dto);
        $properties = $reflection->getProperties();
        $array = [];

        foreach ($properties as $property) {
            $keys = static::getPropertyKey($property, $renameKey);

            if (!$keys->dataKey) continue;
            if (!empty($property->getAttributes(ChangeIgnore::class, ReflectionAttribute::IS_INSTANCEOF))) continue;

            $value = $property->getValue($dto);
            $value = static::convertValue($value, $asSmartArray);
            $array[$keys->dataKey] = $value;
        }

        return $array;
    }

    protected static function convertValue(mixed $value, bool $asSmartArray): mixed
    {
        return match (gettype($value)) {
            TypeHintResolver::ARRAY->value => static::mapArrayWithKeys($value, $asSmartArray),
            TypeHintResolver::OBJECT->value => static::toArray($value),
            default => $value,
        };
    }
}