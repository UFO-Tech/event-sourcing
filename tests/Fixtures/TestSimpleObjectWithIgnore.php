<?php

declare(strict_types = 1);

namespace Ufo\EventSourcing\Tests\Fixtures;

use Ufo\EventSourcing\Attributes\ChangeIgnore;

readonly class TestSimpleObjectWithIgnore
{
    public function __construct(
        public string $name,
        #[ChangeIgnore]
        public string $type
    ) {}
}