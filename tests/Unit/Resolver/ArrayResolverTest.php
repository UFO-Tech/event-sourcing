<?php

declare(strict_types = 1);

namespace Ufo\EventSourcing\Tests\Unit\Resolver;

use PHPUnit\Framework\TestCase;
use Ufo\EventSourcing\Exceptions\NoDiffDetectedException;
use Ufo\EventSourcing\Resolver\ArrayResolver;
use Ufo\EventSourcing\Resolver\CollectionResolver;
use Ufo\EventSourcing\Resolver\ContextDTO;
use Ufo\EventSourcing\Resolver\MainResolver;
use Ufo\EventSourcing\Resolver\ObjectResolver;
use Ufo\EventSourcing\Resolver\ScalarResolver;
use Ufo\EventSourcing\Utils\ValueNormalizer;

class ArrayResolverTest extends TestCase
{
    private ArrayResolver $resolver;

    protected function setUp(): void
    {
        $valueNormalizer = new ValueNormalizer();

        $mainResolver = new MainResolver();
        $mainResolver->addResolver(new ScalarResolver());
        $mainResolver->addResolver(new CollectionResolver($mainResolver));
        $mainResolver->addResolver(new ObjectResolver($mainResolver, $valueNormalizer));
        $this->resolver = new ArrayResolver($mainResolver, $valueNormalizer);
        $mainResolver->addResolver($this->resolver);
    }

    public function testItSupportsSequentialArray(): void
    {
        $this->assertTrue($this->resolver->supportType([1, 2, 3]));
    }

    public function testItDoesNotSupportAssociativeArray(): void
    {
        $this->assertFalse($this->resolver->supportType(['a' => 1, 'b' => 2]));
    }

    public function testItDoesNotSupportAssocPathFromContext(): void
    {
        $ctx = ContextDTO::create(assocPaths: '');
        $this->assertFalse($this->resolver->supportType([1, 2, 3], $ctx));
    }

    public function testItDetectsEqualArrays(): void
    {
        $this->assertTrue($this->resolver->isEqual([1, 2, 3], [1, 2, 3]));
    }

    public function testItDetectsDifferentArrays(): void
    {
        $this->assertFalse($this->resolver->isEqual([1, 2, 3], [1, 2]));
    }

    public function testItResolvesDifferencesBetweenArrays(): void
    {
        $old = [1, 2, 3, 4, 5];
        $new = [2, 4];

        $diff = $this->resolver->resolve($old, $new);

        $this->assertSame([2, 4], $diff);
    }

    public function testItResolvesEmptyDifferenceIfEqual(): void
    {
        $old = [1, 2, 3];
        $new = [1, 2, 3];

        $this->expectException(NoDiffDetectedException::class);

        $this->resolver->resolve($old, $new);
    }
}