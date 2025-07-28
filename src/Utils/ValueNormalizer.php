<?php

declare(strict_types = 1);

namespace Ufo\EventSourcing\Utils;

use Ufo\EventSourcing\Contracts\ValueNormalizerInterface;

class ValueNormalizer implements ValueNormalizerInterface
{
    public function normalize(mixed $value): null|array|int|float|string|bool
    {
        if (!is_object($value)) return $value;

        return ChangeIgnoreTransformer::toArray($value);
    }
}