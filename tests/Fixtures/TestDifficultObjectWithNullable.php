<?php

declare(strict_types = 1);

namespace Ufo\EventSourcing\Tests\Fixtures;


readonly class TestDifficultObjectWithNullable
{
    public function __construct(
        public string $name,
        public ?TestSimpleObject $testDTO,
    ) {}
}