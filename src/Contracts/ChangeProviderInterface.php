<?php

namespace Ufo\EventSourcing\Contracts;

interface ChangeProviderInterface
{
    /**
     * @param string $objectFQCN
     * @param string $id
     * @return array
     */
    public function getChangesFor(string $objectFQCN, string $id): array;
}