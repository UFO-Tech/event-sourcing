<?php

declare(strict_types = 1);

namespace Ufo\EventSourcing\Restorer;

class ObjectDefinition
{
    protected array $changesCollection = [];

    public function __construct(
        protected string $classFQCN,
    ) {}

    public function addChanges(array $changes): static
    {
        $this->changesCollection[] = $changes;
        return $this;
    }

    public function getChangesCollection(): array
    {
        return $this->changesCollection;
    }

    public function getClassFQCN(): string
    {
        return $this->classFQCN;
    }
}