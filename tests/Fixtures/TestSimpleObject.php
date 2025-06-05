<?php

declare(strict_types = 1);

namespace Ufo\EventSourcing\Tests\Fixtures;
class TestSimpleObject
{
    public function __construct(
        readonly public string $name,
        readonly public string $type,
        readonly public array  $data
    ) {}
}