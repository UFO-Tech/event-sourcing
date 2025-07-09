<?php

declare(strict_types = 1);

namespace Ufo\EventSourcing\Restorer\Merger;

use Ufo\EventSourcing\Contracts\ResolverInterface;

class Merger implements MergerInterface
{
    public function merge(array $state, array $changes): array
    {
        $keys = [
            ...array_keys($state),
            ...array_keys($changes),
        ];

        $result = [];
        foreach ($keys as $key) {
            if (array_key_exists($key, $changes) && $changes[$key] === ResolverInterface::DELETE_FLAG) continue;

            $result[$key] = $changes[$key] ?? $state[$key];

            if (is_array($result[$key]) && !array_is_list($result[$key])) {
                $result[$key] = $this->merge($state[$key] ?? [], $changes[$key] ?? []);
            }

        }

        return $result;
    }
}