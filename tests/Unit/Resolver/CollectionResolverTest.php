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

class CollectionResolverTest extends TestCase
{
    private CollectionResolver $resolver;

    protected function setUp(): void
    {
        $mainResolver = new MainResolver();
        $mainResolver->addResolver(new ScalarResolver());
        $mainResolver->addResolver(new ArrayResolver($mainResolver));
        $mainResolver->addResolver(new ObjectResolver($mainResolver));
        $this->resolver = new CollectionResolver($mainResolver);
        $mainResolver->addResolver($this->resolver);
    }

    public function testItDetectsNewItemInCollection(): void
    {
        $old = ['a' => 1];
        $new = ['a' => 1, 'b' => 2];

        $diff = $this->resolver->resolve($old, $new, ContextDTO::create());
        $this->assertSame(['b' => 2], $diff);
    }

    public function testItDetectsDeletedItemInCollection(): void
    {
        $old = ['a' => 1, 'b' => 2];
        $new = ['a' => 1];

        $diff = $this->resolver->resolve(
            $old,
            $new,
            ContextDTO::create(deletePlaceholder: ContextDTO::DELETE_FLAG)
        );

        $this->assertSame(['b' => ContextDTO::DELETE_FLAG], $diff);
    }

    public function testItDetectsChangedItemInCollection(): void
    {
        $old = ['a' => 1, 'b' => 2];
        $new = ['a' => 1, 'b' => 3];

        $diff = $this->resolver->resolve($old, $new);
        $this->assertSame(['b' => 3], $diff);
    }

    public function testItThrowsWhenNoChanges(): void
    {
        $this->expectException(NoDiffDetectedException::class);

        $old = ['a' => 1, 'b' => 2];
        $this->resolver->resolve($old, $old);
    }

    public function testItReturnsSnapshotWhenOldIsNull(): void
    {
        $new = ['a' => 1, 'b' => 2];

        $diff = $this->resolver->resolve(null, $new);
        $this->assertSame($new, $diff);
    }

    public function testItDetectsNestedChanges(): void
    {
        $mainResolver = new MainResolver();
        $mainResolver->addResolver(new ScalarResolver());
        $mainResolver->addResolver(new CollectionResolver($mainResolver));

        $resolver = new CollectionResolver($mainResolver);

        $old = ['group' => ['a' => 1, 'b' => 2]];
        $new = ['group' => ['a' => 1, 'b' => 3]];

        $diff = $resolver->resolve($old, $new, ContextDTO::create()->makeAssocByPath('group'));

        $this->assertSame(['group' => ['b' => 3]], $diff);
    }

    public function testItSkipsKeysWithoutChanges(): void
    {
        $old = ['a' => 1, 'b' => 2, 'c' => 3];
        $new = ['a' => 1, 'b' => 22, 'c' => 3];

        $diff = $this->resolver->resolve($old, $new);

        $this->assertSame(['b' => 22], $diff);
    }

    public function testItDetectsEmptyDiffThrowsException(): void
    {
        $this->expectException(NoDiffDetectedException::class);

        $old = ['a' => 1, 'b' => 2];
        $new = ['a' => 1, 'b' => 2];

        $this->resolver->resolve($old, $new);
    }

    public function testItDetectsMultipleChanges(): void
    {
        $old = ['a' => 1, 'b' => 2];
        $new = ['a' => 10, 'c' => 3];

        $diff = $this->resolver->resolve(
            $old,
            $new,
            ContextDTO::create(deletePlaceholder: '__X__')
        );

        $this->assertSame(['a' => 10, 'b' => '__X__', 'c' => 3], $diff);
    }

    public function testItHandlesEmptyOldCollection(): void
    {
        $new = ['x' => 123, 'y' => 456];
        $diff = $this->resolver->resolve([], $new);

        $this->assertSame($new, $diff);
    }

    public function testItHandlesDeletedKeysFromCollection(): void
    {
        $old = ['a' => ['id' => 1, 'val' => 100], 'b' => ['id' => 2, 'val' => 200]];
        $new = ['b' => ['id' => 2, 'val' => 200], 'c' => ['id' => 3, 'val' => 300]];

        $diff = $this->resolver->resolve($old, $new);

        $this->assertSame([
            'a' => ContextDTO::create()->deletePlaceholder,
            'c' => ['id' => 3, 'val' => 300],
        ], $diff);
    }

    public function testItHandlesDeletedKeyCollection(): void
    {
        $old = ['a' => ContextDTO::create()->deletePlaceholder, 'b' => ['id' => 2, 'val' => 200]];
        $new = ['a' => ['id' => 2, 'val' => 200], 'b' => ['id' => 2, 'val' => 200]];

        $diff = $this->resolver->resolve($old, $new);

        $this->assertSame([
            'a' => ['id' => 2, 'val' => 200],
        ], $diff);
    }
}