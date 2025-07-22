<?php

declare(strict_types = 1);

namespace Ufo\EventSourcing\Restorer\Merger;

use Ufo\EventSourcing\Contracts\MergerInterface;

class Merger implements MergerInterface
{
    public function merge(array $state, array $changes, ?MergeContextDTO $context = null): array
    {
        $context ??= MergeContextDTO::create();

        $keys = [
            ...array_keys($state),
            ...array_keys($changes),
        ];

        $result = [];
        foreach ($keys as $key) {
            if (array_key_exists($key, $changes) && $changes[$key] === $context->deletePlaceholder) continue;

            $result[$key] = array_key_exists($key, $changes) ? $changes[$key] : $state[$key];

            if (is_array($result[$key]) && !array_is_list($result[$key])) {
                $result[$key] = $this->merge($state[$key] ?? [], $changes[$key] ?? []);
            }

        }

        return $result;
    }
}