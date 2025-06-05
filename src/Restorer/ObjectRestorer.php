<?php

declare(strict_types = 1);

namespace Ufo\EventSourcing\Restorer;

use Ufo\DTO\DTOTransformer;
use Ufo\EventSourcing\Contracts\ResolverInterface;
use Ufo\EventSourcing\Contracts\RestorerInterface;
use Ufo\EventSourcing\Resolver\ArrayResolver;
use Ufo\EventSourcing\Resolver\CollectionResolver;

class ObjectRestorer implements RestorerInterface
{
    /**
     * @param ObjectDefinition $objectDefinition
     * @return object
     */
    public function restore(ObjectDefinition $objectDefinition): object
    {
        $data = [];

        foreach ($objectDefinition->getChangesCollection() as $change) {
            $data = $this->merge($data, $change);
        }

        return DTOTransformer::fromArray($objectDefinition->getClassFQCN(), $data);
    }


    protected function merge(array $state, array $changes): array
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