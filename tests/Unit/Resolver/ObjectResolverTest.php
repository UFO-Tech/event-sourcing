<?php

declare(strict_types = 1);

namespace Ufo\EventSourcing\Tests\Unit\Resolver;

use PHPUnit\Framework\TestCase;
use Ufo\EventSourcing\Exceptions\InvalidObjectException;
use Ufo\EventSourcing\Exceptions\NoDiffDetectedException;
use Ufo\EventSourcing\Resolver\ArrayResolver;
use Ufo\EventSourcing\Resolver\CollectionResolver;
use Ufo\EventSourcing\Resolver\ContextDTO;
use Ufo\EventSourcing\Resolver\ObjectResolver;
use Ufo\EventSourcing\Resolver\ScalarResolver;
use Ufo\EventSourcing\Resolver\MainResolver;
use Ufo\EventSourcing\Tests\Fixtures\TestBrokenObject;
use Ufo\EventSourcing\Tests\Fixtures\TestDifficultObjectWithNullable;
use Ufo\EventSourcing\Tests\Fixtures\TestFullIgnoreObject;
use Ufo\EventSourcing\Tests\Fixtures\TestObjectWithCollectionKey;
use Ufo\EventSourcing\Tests\Fixtures\TestSimpleObject;
use Ufo\EventSourcing\Tests\Fixtures\TestSimpleObjectWithIgnore;
use Ufo\EventSourcing\Utils\ValueNormalizer;

class ObjectResolverTest extends TestCase
{
    private ObjectResolver $resolver;

    protected function setUp(): void
    {
        $valueNormalizer = new ValueNormalizer();

        $mainResolver = new MainResolver();
        $mainResolver->addResolver(new ScalarResolver());
        $mainResolver->addResolver(new ArrayResolver($mainResolver, $valueNormalizer));
        $mainResolver->addResolver(new CollectionResolver($mainResolver));
        $this->resolver = new ObjectResolver($mainResolver, $valueNormalizer);
        $mainResolver->addResolver($this->resolver);
    }

    public function testItDetectsSimpleFieldChanges(): void
    {
        $old = new TestSimpleObject('A', 'X', []);
        $new = new TestSimpleObject('B', 'X', []);
        $diff = $this->resolver->resolve($old, $new, ContextDTO::create());
        $this->assertSame(['name' => 'B'], $diff);
    }

    public function testItThrowsWhenNoChanges(): void
    {
        $this->expectException(NoDiffDetectedException::class);
        $obj = new TestSimpleObject('A', 'X', []);
        $this->resolver->resolve($obj, clone $obj);
    }

    public function testItThrowsWhenDifferentClasses(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $old = new \stdClass();
        $new = new TestSimpleObject('A', 'X', []);
        $this->resolver->resolve($old, $new, ContextDTO::create(checkClassEquality: true));
    }

    public function testItReturnsSnapshotWhenOldNull(): void
    {
        $new = new TestSimpleObject('A', 'X', ['v']);
        $diff = $this->resolver->resolve(null, $new);
        $this->assertArrayHasKey('name', $diff);
        $this->assertSame('A', $diff['name']);
    }

    public function testItIgnoresChangeIgnoreFields(): void
    {
        $old = new TestSimpleObjectWithIgnore('A', 'X');
        $new = new TestSimpleObjectWithIgnore('B', 'Y');
        $diff = $this->resolver->resolve($old, $new);
        $this->assertSame(['name' => 'B'], $diff);
        $this->assertArrayNotHasKey('type', $diff);
    }

    public function testItDetectsCollectionChangeWithAsCollection(): void
    {
        $old = new TestObjectWithCollectionKey(
            'parent',
            [
                new TestSimpleObject('first', 'A', ['x']),
                new TestSimpleObject('second', 'B', ['y']),
            ]
        );

        $new = new TestObjectWithCollectionKey(
            'parent',
            [
                new TestSimpleObject('first', 'A', ['x']),
                new TestSimpleObject('second', 'B', ['y', 'z']),
                new TestSimpleObject('third', 'C', ['k']),
            ]
        );

        $diff = $this->resolver->resolve($old, $new);

        $this->assertSame([
            'collection' => [
                1 => ['data' => ['y', 'z']],
                2 => ['name' => 'third', 'type' => 'C', 'data' => ['k']],
            ]
        ], $diff);
    }

    public function testItDetectsCollectionFromContext(): void
    {
        $old = new TestSimpleObject(
            'Name',
            'Type',
            [
                ['sku' => '111', 'qty' => 10],
                ['sku' => '222', 'qty' => 5],
            ]
        );

        $new = new TestSimpleObject(
            'Name',
            'Type',
            [
                ['sku' => '111', 'qty' => 12],
                ['sku' => '222', 'qty' => 5],
                ['sku' => '333', 'qty' => 7],
            ]
        );

        $diff = $this->resolver->resolve(
            $old,
            $new,
            ContextDTO::create()->makeAssocByPath('data')
        );

        $this->assertSame([
            'data' => [
                0 => ['qty' => 12],
                2 => ['sku' => '333', 'qty' => 7],
            ],
        ], $diff);
    }

    public function testItThrowsWhenObjectsHaveDifferentClasses(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Expected objects of the same class/');

        $old = new \stdClass();
        $new = new TestSimpleObject('A', 'B', []);
        $this->resolver->resolve($old, $new, ContextDTO::create(checkClassEquality: true));
    }

    public function testItThrowsWhenNewValueNotInitialized(): void
    {
        $this->expectException(InvalidObjectException::class);

        $old = null;
        $new = new TestBrokenObject();
        $this->resolver->resolve($old, $new);
    }

    public function testItThrowsNoDiffWhenAllFieldsIgnored(): void
    {
        $this->expectException(NoDiffDetectedException::class);

        $obj = new TestFullIgnoreObject('same', 'same');
        $this->resolver->resolve($obj, clone $obj);
    }

    public function testItDetectsObjectSetToNull(): void
    {
        $old = new TestDifficultObjectWithNullable(
            'Same name',
            new TestSimpleObject('Name', 'Type', ['a', 'b'])
        );

        $new = new TestDifficultObjectWithNullable(
            'Same name',
            null
        );

        $diff = $this->resolver->resolve($old, $new);

        $this->assertSame([
            'testDTO' => null
        ], $diff);
    }

    public function testItDetectsObjectChangedFromNull(): void
    {
        $old = new TestDifficultObjectWithNullable(
            'Same name',
            null
        );

        $new = new TestDifficultObjectWithNullable(
            'Same name',
            new TestSimpleObject('Name', 'Type', ['a', 'b'])
        );

        $diff = $this->resolver->resolve($old, $new);

        $this->assertSame([
            'testDTO' => [
                'name' => 'Name',
                'type' => 'Type',
                'data' => ['a', 'b'],
            ]
        ], $diff);
    }
}