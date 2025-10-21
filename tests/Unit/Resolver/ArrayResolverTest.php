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

    protected MainResolver $mainResolver;

    protected function setUp(): void
    {
        $valueNormalizer = new ValueNormalizer();

        $mainResolver = new MainResolver();
        $this->mainResolver = $mainResolver;

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

    public function testItResolvesEmptyDifferenceIfEqualWithAssocPath(): void
    {
        $old = ['a' => 1, 'b' => 2, 'c' => 3];
        $new = [];

        $result = $this->mainResolver->resolve($old, $new);

        $this->assertSame([
            'a' => '__DELETED__',
            'b' => '__DELETED__',
            'c' => '__DELETED__',
        ], $result);

        $old2 = ['a', 'b', 'c'];
        $new2 = [];

        $result2 = $this->mainResolver->resolve($old2, $new2);
        $this->assertSame([], $result2);
    }

    public function testItResolvesEmptyDifferenceIfEqualWithAssocPathFromContext(): void
    {
        $old = ['a', 'b', 'c'];
        $new = [
            'a' => 'a',
            'b' => 'b',
            'c' => 'c'
        ];

        $result = $this->mainResolver->resolve($old, $new);
        $this->assertSame([
            0 => '__DELETED__',
            1 => '__DELETED__',
            2 => '__DELETED__',
            'a' => 'a',
            'b' => 'b',
            'c' => 'c'
        ], $result);
    }
}