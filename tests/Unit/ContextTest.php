<?php

declare(strict_types = 1);

namespace Ufo\EventSourcing\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ufo\EventSourcing\Resolver\ContextDTO;

class ContextTest extends TestCase
{
    public function testItDetectsAssocByPattern(): void
    {
        $ctx = ContextDTO::create(
            assocPaths: [
                'collection.$.members.$.users',
            ]
        );

        $this->assertTrue($ctx->forPath('collection.0.members.1.users')->isCurrentPathAssoc());

        $ctx = ContextDTO::create(
            assocPaths: [
                'collection',
                'collection.$.members',
            ]
        );

        $this->assertTrue($ctx->forPath('collection')->isCurrentPathAssoc());
        $this->assertTrue($ctx->forPath('collection.0.members')->isCurrentPathAssoc());
        $this->assertFalse($ctx->forPath('collection.0.members.1.name')->isCurrentPathAssoc());
    }

    public function testItSupportsPlainPathAndPatternTogether(): void
    {
        $ctx = ContextDTO::create(
            assocPaths: [
                'collection.static.members',
                'collection.$.members.$.users',
            ]
        );

        $this->assertTrue($ctx->forPath('collection.static.members')->isCurrentPathAssoc());
        $this->assertTrue($ctx->forPath('collection.0.members.1.users')->isCurrentPathAssoc());
        $this->assertFalse($ctx->forPath('collection.0.members.1')->isCurrentPathAssoc());
    }

    public function testItNotDetectsUnrelatedPaths(): void
    {
        $ctx = ContextDTO::create(
            assocPaths: [
                'collection.$.members.$.users',
            ]
        );

        $this->assertFalse($ctx->forPath('collection.0.members.1.groups')->isCurrentPathAssoc());
        $this->assertFalse($ctx->forPath('collection')->isCurrentPathAssoc());
    }

    public function testItDetectsAssocByExactPath(): void
    {
        $ctx = ContextDTO::create(assocPaths: ['collection.members.users']);
        $this->assertTrue($ctx->forPath('collection.members.users')->isCurrentPathAssoc());
        $this->assertFalse($ctx->forPath('collection.members')->isCurrentPathAssoc());
        $this->assertFalse($ctx->forPath('collection.members.users.extra')->isCurrentPathAssoc());
    }

    public function testItDetectsAssocBySingleLevelPattern(): void
    {
        $ctx = ContextDTO::create(assocPaths: ['collection.$']);
        $this->assertTrue($ctx->forPath('collection.0')->isCurrentPathAssoc());
        $this->assertTrue($ctx->forPath('collection.123')->isCurrentPathAssoc());
        $this->assertFalse($ctx->forPath('collection')->isCurrentPathAssoc());
    }

    public function testItDetectsAssocByNestedPattern(): void
    {
        $ctx = ContextDTO::create(assocPaths: ['collection.$.members.$']);
        $this->assertTrue($ctx->forPath('collection.0.members.0')->isCurrentPathAssoc());
        $this->assertTrue($ctx->forPath('collection.1.members.9')->isCurrentPathAssoc());
        $this->assertFalse($ctx->forPath('collection.0.members')->isCurrentPathAssoc());
    }

    public function testItDoesNotMatchDeeperPaths(): void
    {
        $ctx = ContextDTO::create(assocPaths: ['collection.$.members.$.users']);
        $this->assertTrue($ctx->forPath('collection.0.members.1.users')->isCurrentPathAssoc());
        $this->assertFalse($ctx->forPath('collection.0.members.1.users.1.name')->isCurrentPathAssoc());
        $this->assertFalse($ctx->forPath('collection.0.members.1')->isCurrentPathAssoc());
    }

    public function testItCombinesPlainAndPatternPaths(): void
    {
        $ctx = ContextDTO::create(assocPaths: ['a.b.c', 'collection.$.members.$.users']);
        $this->assertTrue($ctx->forPath('a.b.c')->isCurrentPathAssoc());
        $this->assertTrue($ctx->forPath('collection.1.members.2.users')->isCurrentPathAssoc());
        $this->assertFalse($ctx->forPath('collection.1.members.2.groups')->isCurrentPathAssoc());
    }

    public function testEmptyAssocPathsNeverMatches(): void
    {
        $ctx = ContextDTO::create(assocPaths: []);
        $this->assertFalse($ctx->forPath('any.path')->isCurrentPathAssoc());
    }

    public function testRootLevelPattern(): void
    {
        $ctx = ContextDTO::create(param: '', assocPaths: ['$']);
        $this->assertTrue($ctx->forPath('0')->isCurrentPathAssoc());
        $this->assertTrue($ctx->forPath('1')->isCurrentPathAssoc());
        $this->assertFalse($ctx->forPath('0')->forPath('name')->isCurrentPathAssoc());
    }

    public function testForPathMaintainsPreviousState(): void
    {
        $ctx = ContextDTO::create(assocPaths: ['collection.$.members']);
        $next = $ctx->forPath('collection')->forPath('0')->forPath('members');
        $this->assertTrue($next->isCurrentPathAssoc());
        $this->assertFalse($ctx->isCurrentPathAssoc(), 'root context must stay clean');
    }

    public function testItReturnsCorrectPathAndParam(): void
    {
        $ctx = ContextDTO::create(param: 'start');
        $this->assertSame('start', $ctx->getPath());
        $this->assertSame('start', $ctx->getParam());

        $ctxNext = $ctx->forPath('next');
        $this->assertSame('start.next', $ctxNext->getPath());
        $this->assertSame('next', $ctxNext->getParam());
    }

    public function testItKeepsDeletePlaceholder(): void
    {
        $ctx = ContextDTO::create(deletePlaceholder: '__CUSTOM_DELETE__');
        $this->assertSame('__CUSTOM_DELETE__', $ctx->deletePlaceholder);
    }

    public function testItMakesAssocByPathProperly(): void
    {
        $ctx = ContextDTO::create(param: 'root');
        $ctx->makeAssocByPath('a.b')->makeAssocByPath('collection.$');

        $reflection = new \ReflectionClass($ctx);

        $assoc = $reflection->getProperty('assocPaths');
        $pattern = $reflection->getProperty('patternPaths');

        $this->assertArrayHasKey('root.a.b', $assoc->getValue($ctx));
        $this->assertContains('root.collection.*', $pattern->getValue($ctx));
    }

    public function testItDetectsAssocDirectlyViaIsAssoc(): void
    {
        $ctx = ContextDTO::create(param: 'root');
        $ctx->makeAssocByPath('foo.bar');

        $this->assertTrue($ctx->isAssoc('root.foo.bar'));
        $this->assertFalse($ctx->isAssoc('root.foo.baz'));
    }
}