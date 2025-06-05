<?php

declare(strict_types = 1);

namespace Ufo\EventSourcing\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class ChangeIgnore
{
    //

}