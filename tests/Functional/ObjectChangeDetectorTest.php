<?php

declare(strict_types = 1);

namespace Ufo\EventSourcing\Tests\Functional;

use PHPUnit\Framework\TestCase;
use Ufo\EventSourcing\Exceptions\NoDiffDetectedException;
use Ufo\EventSourcing\Resolver\ArrayResolver;
use Ufo\EventSourcing\Resolver\CollectionResolver;
use Ufo\EventSourcing\Resolver\MainResolver;
use Ufo\EventSourcing\Resolver\ObjectResolver;
use Ufo\EventSourcing\Resolver\ScalarResolver;
use Ufo\EventSourcing\Restorer\ObjectDefinition;
use Ufo\EventSourcing\Restorer\ObjectRestorer;
use Ufo\EventSourcing\Tests\Fixtures\TestDifficultObject;
use Ufo\EventSourcing\Tests\Fixtures\TestSimpleObject;
use Ufo\EventSourcing\Tests\Fixtures\TestSimpleObjectWithIgnore;
use Ufo\EventSourcing\Utils\ValueNormalizer;

class ObjectChangeDetectorTest extends TestCase
{
    protected MainResolver $mainResolver;

    public function setUp(): void
    {
        $this->mainResolver = new MainResolver();
        $objectResolver = new ObjectResolver($this->mainResolver);
        $collectionResolver = new CollectionResolver($this->mainResolver, new ValueNormalizer());
        $arrayResolver = new ArrayResolver();
        $scalarResolver = new ScalarResolver();

        foreach ([$objectResolver, $arrayResolver, $scalarResolver, $collectionResolver] as $resolver) {
            $this->mainResolver->addResolver($resolver);
        }
    }

    /**
     * Tests for the ObjectChangeDetector class to ensure proper functionality of the resolve method.
     */

    public function testNoChangesDetected(): void
    {
        $this->expectException(NoDiffDetectedException::class);
        $oldObject = new TestSimpleObject(
            'test1',
            'test2',
            ['test3', 'test4', 'test5']
        );

        $newObject = clone $oldObject;

        $this->mainResolver->resolve($oldObject, $newObject);
    }

    public function testChangesDetected(): void
    {
        $oldObject = new TestSimpleObject(
            'Old Name',
            'OldType',
            ['test3', 'test4', 'test5']
        );

        $newObject = new TestSimpleObject(
            'New Name',
            'OldType',
            ['test3', 'test4', 'test5']
        );

        $result = $this->mainResolver->resolve($oldObject, $newObject);

        $this->assertNotEmpty($result);
        $this->assertArrayHasKey('name', $result);
        $this->assertEquals('New Name', $result['name']);
    }

    public function testObjectChangeDetected(): void
    {
        $oldSimpleObject = new TestSimpleObject(
            'Old Name',
            'OldType',
            ['test3', 'test4', 'test5']
        );

        $newSimpleObject = new TestSimpleObject(
            'New Name',
            'OldType',
            ['test3', 'test4', 'test5']
        );

        $oldObject = new TestDifficultObject(
            'Old name',
            $oldSimpleObject
        );

        $newObject = new TestDifficultObject(
            'New Name',
            $newSimpleObject
        );

        $result = $this->mainResolver->resolve($oldObject, $newObject);

        $this->assertNotEmpty($result);
        $this->assertArrayHasKey('testDTO', $result);
        $this->assertEquals('New Name', $result['testDTO']['name']);
        $this->assertEquals('New Name', $result['name']);
    }

    public function testArrayChangeDetected(): void
    {
        $oldArray = ['test1', 'test2', 'test3'];
        $newArray = ['test1', 'test2', 'test3', 'test4'];

        $result = $this->mainResolver->resolve($oldArray, $newArray);
        $this->assertSame($result, $newArray);
    }

    public function testCollectionChangeDetected(): void
    {
        $oldSimpleObject = new TestSimpleObject(
            'Old Name',
            'OldType',
            ['test3', 'test4', 'test5']
        );

        $newSimpleObject = new TestSimpleObject(
            'New Name',
            'OldType',
            ['test3', 'test4', 'test5']
        );

        $oldObject = new TestDifficultObject(
            'Old Name',
            $oldSimpleObject
        );

        $newObject = new TestDifficultObject(
            'New Name',
            $newSimpleObject
        );

        $oldCollection = [
            'object1' => $oldObject
        ];

        $newCollection = [
            'object1' => $newObject,
            'object2' => $oldObject
        ];

        $expected = [
            'object1' => [
                'name' => 'New Name',
                'testDTO' => [
                    'name' => 'New Name',
                ],
            ],
            'object2' => [
                'name' => 'Old Name',
                'testDTO' => [
                    'name' => 'Old Name',
                    'type' => 'OldType',
                    'data' => ['test3', 'test4', 'test5'],
                ]
            ],
        ];


        $result = $this->mainResolver->resolve($oldCollection, $newCollection);
        $this->assertSame($expected, $result);
    }

    public function testChangeIgnore(): void
    {
        $oldSimpleObject = new TestSimpleObjectWithIgnore(
            'Old Name',
            'OldType'
        );

        $newSimpleObject = new TestSimpleObjectWithIgnore(
            'New Name',
            'NewType'
        );

        $result = $this->mainResolver->resolve($oldSimpleObject, $newSimpleObject);

        $this->assertArrayNotHasKey('type', $result);

        $base = [
            'name' => 'Old Name',
            'type' => 'OldType',
        ];

        $objectDefinition = (new ObjectDefinition(TestSimpleObjectWithIgnore::class))
            ->addChanges($base)
            ->addChanges($result);

        /**
         * @var TestSimpleObjectWithIgnore $restoreObject
         */
        $restoreObject = (new ObjectRestorer())->restore($objectDefinition);

        $this->assertEquals($restoreObject->name, $newSimpleObject->name);
        $this->assertEquals($restoreObject->type, $oldSimpleObject->type);
    }
}