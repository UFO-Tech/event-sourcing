<?php

declare(strict_types = 1);

namespace Ufo\EventSourcing\Tests\Fixtures;

use Ufo\EventSourcing\Attributes\AsCollection;

class TestObjectWithCollection
{
    #[AsCollection(TestSimpleObject::class)]
    public array $collection = [];

    public function __construct(
        readonly public string $name,
        readonly public TestSimpleObject $testDTO
    )
    {
        $this->collection[] = $testDTO;
    }
}