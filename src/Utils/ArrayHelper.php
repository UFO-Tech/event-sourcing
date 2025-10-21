<?php

declare(strict_types = 1);

namespace Ufo\EventSourcing\Utils;

class ArrayHelper
{
    public static function isAssociative(array $array): bool
    {
//        // Якщо ключі масиву не є послідовними числовими індексами, то це асоціативний масив
//        return array_keys($array) !== range(0, count($array) - 1);


        $result = array_all($array, fn ($item, $key) => !is_int($key));
        return (bool)$result;
    }
}