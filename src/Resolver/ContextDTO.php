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
    public const bool DEFAULT_SUPPRESS_WARNINGS = true;

    // logic
    public const string ROOT_PARAM = 'root';
    public const string ORIGINAL_KEY_FIELD = '_originalKey';
    public const string DELETE_FLAG = '__DELETED__';


    protected array $assocPaths = [];
    protected array $assocKeys = [];
    protected string $path = self::ROOT_PARAM;

    public function __construct(
        public string $param = self::ROOT_PARAM,
        readonly public string $deletePlaceholder = self::DELETE_FLAG,
        array|string $assocPaths = [],
        protected bool $suppressWarnings = self::DEFAULT_SUPPRESS_WARNINGS,
    )
    {
        if (is_string($assocPaths)) {
            $this->registerAssocPath($this->path, $assocPaths);
        } elseif (is_array($assocPaths)) {
            foreach ($assocPaths as $path => $key) {
                if (is_int($path)) {
                    $this->registerAssocPath($this->path, (string) $key);
                } elseif (is_string($path)) {
                    $this->registerAssocPath($this->path, $path, is_string($key) ? $key : null);
                }
            }
        }
    }

    public static function create(
        string $param = self::ROOT_PARAM,
        string $deletePlaceholder = self::DELETE_FLAG,
        array|string $assocPaths = [],
        bool $suppressWarnings = self::DEFAULT_SUPPRESS_WARNINGS,
    ): static {
        return new static($param, $deletePlaceholder, $assocPaths, $suppressWarnings);
    }

    protected function registerAssocPath(string $basePath, string $relativePath, ?string $keyField = null): void
    {
        $fullPath = $basePath . self::PATH_SEPARATOR . $relativePath;
        $this->assocPaths[$fullPath] = true;
        if ($keyField !== null) {
            $this->assocKeys[$fullPath] = $keyField;
        }
    }

    public function forPath(string $next): static
    {
        $ctx = clone $this;
        $ctx->path = $this->path . self::PATH_SEPARATOR . $next;
        $ctx->param = $next;
        return $ctx;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function isCurrentPathAssoc(): bool
    {
        return $this->assocPaths[$this->path] ?? false;
    }

    public function getCurrentPathKeyField(): ?string
    {
        return $this->assocKeys[$this->path] ?? null;
    }

    public function makeAssocByPath(string $path = '', ?string $keyField = null): static
    {
        $fullPath = $path === '' ? $this->path : $this->path . self::PATH_SEPARATOR . $path;
        $this->assocPaths[$fullPath] = true;
        if ($keyField !== null) {
            $this->assocKeys[$fullPath] = $keyField;
        }

        return $this;
    }

    public function isAssoc(string $path): bool
    {
        return $this->assocPaths[$path] ?? false;
    }

    public function getAssocKeyField(string $path): ?string
    {
        return $this->assocKeys[$path] ?? null;
    }

    public function withoutWarnings(): static
    {
        $this->suppressWarnings = true;

        return $this;
    }

    public function withWarnings(): static
    {
        $this->suppressWarnings = false;

        return $this;
    }

    public function isWarningsSuppressed(): bool
    {
        return $this->suppressWarnings;
    }
}