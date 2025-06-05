<?php

namespace Ufo\EventSourcing\Contracts;

interface ValueNormalizerInterface
{
    public function normalize(mixed $value): null|array|int|float|string|bool;
}