<?php

declare(strict_types = 1);

namespace Ufo\EventSourcing\Contracts;

interface MergerInterface
{
    public function merge(array $state, array $changes): array;
}