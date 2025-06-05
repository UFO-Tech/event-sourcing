<?php

namespace Ufo\EventSourcing\Contracts;


use Ufo\EventSourcing\Restorer\ObjectDefinition;

interface RestorerInterface
{
    public function restore(ObjectDefinition $objectDefinition): object;
}