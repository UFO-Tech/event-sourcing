<?php

declare(strict_types = 1);

namespace Ufo\EventSourcing\Tests\Fixtures;

class ProductDTO
{
    public function __construct(
        public int $quantity
    ) {}
}