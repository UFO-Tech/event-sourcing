<?php

declare(strict_types = 1);

namespace Ufo\EventSourcing\Tests\Functional;

use PHPUnit\Framework\TestCase;
use Ufo\EventSourcing\Exceptions\NoDiffDetectedException;
use Ufo\EventSourcing\Factory\DefaultResolverFactory;
use Ufo\EventSourcing\Resolver\ContextDTO;
use Ufo\EventSourcing\Resolver\MainResolver;
use Ufo\EventSourcing\Restorer\Merger\Merger;
use Ufo\EventSourcing\Restorer\ObjectDefinition;
use Ufo\EventSourcing\Restorer\ObjectRestorer;
use Ufo\EventSourcing\Tests\Fixtures\TestDifficultObject;
use Ufo\EventSourcing\Tests\Fixtures\TestObjectWithCollectionKey;
use Ufo\EventSourcing\Tests\Fixtures\TestSimpleObject;
use Ufo\EventSourcing\Tests\Fixtures\TestSimpleObjectWithIgnore;

class ObjectChangeDetectorTest extends TestCase
{
    protected MainResolver $mainResolver;

    public function setUp(): void
    {
        $factory = new DefaultResolverFactory();

        $this->mainResolver = $factory->create();
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
        $restoreObject = (new ObjectRestorer(new Merger()))->restore($objectDefinition);

        $this->assertEquals($restoreObject->name, $newSimpleObject->name);
        $this->assertEquals($restoreObject->type, $oldSimpleObject->type);
    }

    public function testItResolvesDifferencesBetweenSales(): void
    {
        $sale1 = [
            'id' => 'TM-20250314-02dc3e434a49-h1-f2',
            'marketId' => '0950a0c5-fe39-4c57-aba2-02dc3e434a49',
            'paymentDate' => '2025-07-24',
            'paymentForm' => '2',
            'amountValue' => 764.64,
            'comments' => null,
            'consignmentId' => null,
            'products' => [
                4820197564194 => [
                    'barcode' => '4820197564194',
                    'quantity' => 4,
                    'uniquePresentCode' => null,
                ],
                4820197566105 => [
                    'barcode' => '4820197566105',
                    'quantity' => 20,
                    'uniquePresentCode' => null,
                ],
            ],
            'isCarrier' => false,
            'isSelfDelivery' => false,
        ];

        $sale2 = $sale1;
        $sale2['amountValue'] = 800.00;
        $sale2['products']['4820197566105']['quantity'] = 25;

        $diff = $this->mainResolver->resolve(
            $sale1,
            $sale2,
            (ContextDTO::create())
                ->makeAssocByPath('products')
        );

        self::assertSame(
            [
                'amountValue' => 800.00,
                'products' => [
                    '4820197566105' => [
                        'quantity' => 25
                    ]
                ]
            ],
            $diff
        );

        $merger = new Merger();
        $restored = $merger->merge($sale1, $diff);

        self::assertSame($sale2, $restored);
    }

    public function testItResolveDifferencesBetweenArrays(): void
    {
        $data1 = [1, 2, 3, 4, 5];
        $data2 = array_filter($data1, fn (int $v) => $v % 2 === 0);
        $diff = $this->mainResolver->resolve($data1, $data2);

        $this->assertSame($diff, [2, 4]);
    }

    public function testItDetectsNewKeyInAssocCollection(): void
    {
        $old = ['a' => 1, 'b' => 2];
        $new = ['a' => 1, 'b' => 2, 'c' => 3];

        $diff = $this->mainResolver->resolve($old, $new);

        $this->assertSame(['c' => 3], $diff);
    }

    public function testItMarksDeletedKeyWithPlaceholder(): void
    {
        $old = ['a' => 1, 'b' => 2];
        $new = ['a' => 1];

        $deletingKey = '__NEW_DELETED__';
        $diff = $this->mainResolver->resolve(
            $old,
            $new,
            ContextDTO::create(
                deletePlaceholder: $deletingKey
            )
        );

        $this->assertSame(['b' => $deletingKey], $diff);
    }

    public function testItDetectsNestedObjectChangesInCollection(): void
    {
        $old = [
            'products' => [
                '4820197566105' => (object)['quantity' => 10],
            ],
        ];

        $new = [
            'products' => [
                '4820197566105' => (object)['quantity' => 15],
            ],
        ];

        $diff = $this->mainResolver->resolve(
            $old,
            $new,
            (ContextDTO::create())->makeAssocByPath('products')
        );

        $this->assertSame(['products' => ['4820197566105' => ['quantity' => 15]]], $diff);
    }

    public function testItDetectsFullSnapshotWhenOldValueIsNull(): void
    {
        $new = new TestSimpleObject('Test', 'Type', ['A', 'B']);
        $diff = $this->mainResolver->resolve(null, $new);

        $this->assertArrayHasKey('name', $diff);
        $this->assertArrayHasKey('type', $diff);
        $this->assertArrayHasKey('data', $diff);
        $this->assertSame('Test', $diff['name']);
        $this->assertSame('Type', $diff['type']);
        $this->assertSame(['A', 'B'], $diff['data']);
    }

    public function testItIgnoresFieldsViaAttribute(): void
    {
        $old = new TestSimpleObjectWithIgnore('Old', 'TypeA');
        $new = new TestSimpleObjectWithIgnore('New', 'TypeB');

        $diff = $this->mainResolver->resolve($old, $new);
        $this->assertArrayHasKey('name', $diff);
        $this->assertArrayNotHasKey('type', $diff);
    }

    public function testItDetectsAssocCollectionByKey(): void
    {
        $old = [
            ['sku' => '111', 'qty' => 10],
            ['sku' => '222', 'qty' => 5],
        ];

        $new = [
            ['sku' => '111', 'qty' => 12],
            ['sku' => '222', 'qty' => 5],
            ['sku' => '333', 'qty' => 7],
        ];

        $diff = $this->mainResolver->resolve(
            $old,
            $new,
            ContextDTO::create(assocPaths: '')
        );

        $this->assertSame([
            0 => ['qty' => 12],
            2 => ['sku' => '333', 'qty' => 7],
        ], $diff);
    }

    public function testItDetectsCollectionChangesViaAttribute(): void
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
                new TestSimpleObject('second', 'B', ['y', 'z']), // добавился элемент в data
                new TestSimpleObject('third', 'C', ['new']), // новый элемент
            ]
        );

        $diff = $this->mainResolver->resolve($old, $new);

        $this->assertSame([
            'collection' => [
                1 => [
                    'data' => ['y', 'z']
                ],
                2 => [
                    'name' => 'third',
                    'type' => 'C',
                    'data' => ['new']
                ],
            ],
        ], $diff);
    }
}