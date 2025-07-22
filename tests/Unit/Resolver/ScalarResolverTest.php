<?php

declare(strict_types = 1);

namespace Ufo\EventSourcing\Tests\Unit\Resolver;

use PHPUnit\Framework\TestCase;
use Ufo\EventSourcing\Resolver\ScalarResolver;

class ScalarResolverTest extends TestCase
{
    private ScalarResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new ScalarResolver();
    }

    public function testItSupportsScalars(): void
    {
        $this->assertTrue($this->resolver->supportType(42));
        $this->assertTrue($this->resolver->supportType(3.14));
        $this->assertTrue($this->resolver->supportType('hello'));
        $this->assertTrue($this->resolver->supportType(true));

        $this->assertFalse($this->resolver->supportType(['array']));
        $this->assertFalse($this->resolver->supportType((object)['a' => 1]));
    }

    public function testItDetectsEqualScalars(): void
    {
        $this->assertTrue($this->resolver->isEqual(100, 100));
        $this->assertTrue($this->resolver->isEqual('text', 'text'));
        $this->assertTrue($this->resolver->isEqual(true, true));
    }

    public function testItDetectsNonEqualScalars(): void
    {
        $this->assertFalse($this->resolver->isEqual(100, 200));
        $this->assertFalse($this->resolver->isEqual('text', 'other'));
        $this->assertFalse($this->resolver->isEqual(true, false));
    }
}