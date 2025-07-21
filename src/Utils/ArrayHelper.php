<?php

declare(strict_types = 1);

namespace Ufo\EventSourcing\Utils;

class ArrayHelper
{
    public static function isAssociative(array $array): bool
    {
        $result = array_all($array, fn ($item, $key) => !is_int($key));
        return (bool)$result;
    }
}