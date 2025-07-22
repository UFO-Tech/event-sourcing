<?php

declare(strict_types = 1);

namespace Ufo\EventSourcing\Tests\Fixtures;

use Ufo\EventSourcing\Attributes\AsCollection;
use Ufo\EventSourcing\Tests\Fixtures\TestSimpleObject;

class TestObjectWithCollectionKey
{
    public function __construct(
        readonly public string $name,
        #[AsCollection(TestSimpleObject::class)]
        public array $collection = []
    ) {}
}