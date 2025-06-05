<?php

namespace Ufo\EventSourcing\Contracts;

interface MainResolverInterface
{
    public function addResolver(ResolverInterface $resolver): void;

}