<?php

namespace Ufo\EventSourcing\Contracts;

interface MainResolverFactoryInterface
{
    public function create(): MainResolverInterface;
}