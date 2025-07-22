<?php

declare(strict_types = 1);

namespace Ufo\EventSourcing\Restorer\Merger;

use Ufo\DTO\ArrayConstructibleTrait;
use Ufo\DTO\Interfaces\IArrayConstructible;
use Ufo\EventSourcing\Resolver\ContextDTO;

class MergeContextDTO implements IArrayConstructible
{
    use ArrayConstructibleTrait;

    public const string DELETE_FLAG = ContextDTO::DELETE_FLAG;

    public function __construct(
        readonly public string $deletePlaceholder = self::DELETE_FLAG,
    ) {}

    public static function create(
        string $deletePlaceholder = self::DELETE_FLAG,
    ): static
    {
        return new static($deletePlaceholder);
    }
}