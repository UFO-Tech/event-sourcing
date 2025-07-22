<?php

declare(strict_types = 1);

namespace Ufo\EventSourcing\Tests\Fixtures;

use Ufo\EventSourcing\Attributes\ChangeIgnore;

class TestFullIgnoreObject
{
    #[ChangeIgnore]
    public string $name;
}