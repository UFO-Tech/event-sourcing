<?php

namespace Ufo\EventSourcing\Contracts;

use Ufo\EventSourcing\Exceptions\InvalidObjectException;
use Ufo\EventSourcing\Exceptions\NoDiffDetectedException;
use Ufo\EventSourcing\Resolver\ContextDTO;

interface ResolverInterface
{
    public const string ROOT_PARAM = 'root';
    public const string DELETE_FLAG = '__DELETED__';

    /**
     * @throws NoDiffDetectedException|InvalidObjectException
     */
    public function resolve(mixed $oldValue, mixed $newValue, ?ContextDTO $context = null): mixed;

    public function supportType(mixed $value): bool;
}