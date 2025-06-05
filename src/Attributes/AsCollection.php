<?php

declare(strict_types = 1);

namespace Ufo\EventSourcing\Attributes;

use Attribute;
use Ufo\DTO\Attributes\AttrDTO;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class AsCollection extends AttrDTO
{
    public function __construct(string $dtoFQCN, ?string $transformerFQCN = null)
    {
        parent::__construct($dtoFQCN, true, [], $transformerFQCN);
    }
}