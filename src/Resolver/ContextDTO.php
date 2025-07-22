<?php

declare(strict_types=1);

namespace Ufo\EventSourcing\Resolver;

use Ufo\DTO\ArrayConstructibleTrait;
use Ufo\DTO\ArrayConvertibleTrait;
use Ufo\DTO\Interfaces\IArrayConstructible;
use Ufo\DTO\Interfaces\IArrayConvertible;

class ContextDTO implements IArrayConstructible, IArrayConvertible
{
    use ArrayConvertibleTrait, ArrayConstructibleTrait;

    // system
    public const string PATH_SEPARATOR = '.';

    // logic
    public const string ROOT_PARAM = 'root';
    public const string DELETE_FLAG = '__DELETED__';


    protected array $assocPaths = [];
    protected array $patternPaths = [];
    protected string $path = self::ROOT_PARAM;

    public function __construct(
        readonly public string $deletePlaceholder = self::DELETE_FLAG,
        protected string $param = self::ROOT_PARAM,
        array|string $assocPaths = []
    )
    {
        $this->path = $param;

        if (is_string($assocPaths)) {
            $this->makeAssocByPath($assocPaths);
        } elseif (is_array($assocPaths)) {
            foreach ($assocPaths as $path) {
                if (is_int($path)) {
                    $this->makeAssocByPath((string) $path);
                } elseif (is_string($path)) {
                    $this->makeAssocByPath($path);
                }
            }
        }
    }

    public function getParam(): string
    {
        return $this->param;
    }

    public static function create(
        string $param = self::ROOT_PARAM,
        string $deletePlaceholder = self::DELETE_FLAG,
        array|string $assocPaths = []
    ): static {
        return new static($deletePlaceholder, $param, $assocPaths);
    }

    public function forPath(string $nextParam): static
    {
        $ctx = clone $this;
        $ctx->path = $this->path . self::PATH_SEPARATOR . $nextParam;
        $ctx->param = $nextParam;
        return $ctx;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function isCurrentPathAssoc(): bool
    {
        $isAssoc = array_key_exists($this->path, $this->assocPaths);

        if (!$isAssoc) {
            foreach ($this->patternPaths as $pattern) {
                if (
                    substr_count($pattern, '.') === substr_count($this->path, '.')
                    && fnmatch($pattern, $this->path)
                ) {
                    $isAssoc = true;
                    break;
                }
            }
        }

        return $isAssoc;
    }

    public function makeAssocByPath(string $path): static
    {
        $fullPath = $path === '' ? $this->path : $this->path . self::PATH_SEPARATOR . $path;

        if (str_contains($fullPath, '$')) {
            $this->patternPaths[] = str_replace('$', '*', $fullPath);
        } else {
            $this->assocPaths[$fullPath] = true;
        }

        return $this;
    }

    public function isAssoc(string $path): bool
    {
        return $this->assocPaths[$path] ?? false;
    }
}