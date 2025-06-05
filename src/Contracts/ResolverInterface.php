<?php

namespace Ufo\EventSourcing\Contracts;

use Ufo\EventSourcing\Exceptions\NoDiffDetectedException;

interface ResolverInterface
{
    public const string DEFAULT_PARAM_NAME = 'root';
    public const string DELETE_FLAG = '__DELETED__';

    /**
     * @throws NoDiffDetectedException
     */
    public function resolve(mixed $oldValue, mixed $newValue, string $paramName = self::DEFAULT_PARAM_NAME): mixed;

    public function supportType(mixed $value): bool;
}