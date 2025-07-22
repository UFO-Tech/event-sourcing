<?php

declare(strict_types = 1);

namespace Ufo\EventSourcing\Restorer;

use Ufo\DTO\DTOTransformer;
use Ufo\EventSourcing\Contracts\MergerInterface;
use Ufo\EventSourcing\Contracts\RestorerInterface;

class ObjectRestorer implements RestorerInterface
{
    public function __construct(
        protected MergerInterface $merger
    ) {}

    /**
     * @param ObjectDefinition $objectDefinition
     * @return object
     */
    public function restore(ObjectDefinition $objectDefinition): object
    {
        $data = [];

        foreach ($objectDefinition->getChangesCollection() as $change) {
            $data = $this->merger->merge($data, $change, $objectDefinition->getContext());
        }

        return DTOTransformer::fromArray($objectDefinition->getClassFQCN(), $data);
    }
}