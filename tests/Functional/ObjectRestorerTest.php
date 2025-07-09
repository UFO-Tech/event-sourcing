<?php

namespace Ufo\EventSourcing\Tests\Functional;

use PHPUnit\Framework\TestCase;
use Ufo\EventSourcing\Resolver\CollectionResolver;
use Ufo\EventSourcing\Restorer\Merger\Merger;
use Ufo\EventSourcing\Restorer\ObjectDefinition;
use Ufo\EventSourcing\Restorer\ObjectRestorer;
use Ufo\EventSourcing\Tests\Fixtures\TestObjectWithCollection;
use Ufo\EventSourcing\Tests\Fixtures\TestSimpleObject;

class ObjectRestorerTest extends TestCase
{
    protected ObjectRestorer $objectRestorer;

    protected function setUp(): void
    {
        $this->objectRestorer = new ObjectRestorer(
            new Merger()
        );
    }

    /**
     * Test that restore method successfully restores an object with valid data.
     */
    public function testSimpleRestoreSuccess(): void
    {
        $data1 = [
            'name' => 'name 1',
            'type' => 'type 1',
            'data' => [
                'test1',
                'test2',
                'test3'
            ],
        ];

        $data2 = [
            'name' => 'name 2',
            'type' => 'type 2',
            'data' => [
                'test1',
                'test2',
                'test3'
            ],
        ];

        $objectDefinition = new ObjectDefinition(TestSimpleObject::class);
        $objectDefinition->addChanges($data1)->addChanges($data2);;

        $result = $this->objectRestorer->restore($objectDefinition);

        $this->assertInstanceOf( TestSimpleObject::class, $result);
    }

    public function testDifficultRestoreSuccess(): void
    {
        $data1 = [
            'name' => 'name 1',
            'testDTO' => [
                'name' => 'name 1',
                'type' => 'type 1',
                'data' => [
                    'test1',
                    'test2',
                    'test3'
                ],
            ],
            'collection' => [
                'type 1' => [
                    'name' => 'name 1',
                    'type' => 'type 1',
                    'data' => [
                        'test1',
                        'test2',
                        'test3'
                    ],
                ],
                'type 2' => [
                    'name' => 'name 2',
                    'type' => 'type 2',
                    'data' => [
                        'test1',
                        'test2',
                        'test3'
                    ],
                ]
            ]
        ];


        $data2 = [
            'name' => 'name 2',
            'collection' => [
                'type 1' => [
                    'name' => 'name 2',
                ],
                'type 2' => CollectionResolver::DELETE_FLAG
            ]
        ];

        $objectDefinition = new ObjectDefinition(TestObjectWithCollection::class);
        $objectDefinition->addChanges($data1)->addChanges($data2);

        $result = $this->objectRestorer->restore($objectDefinition);

        $this->assertInstanceOf( TestObjectWithCollection::class, $result);
        $this->assertArrayNotHasKey('type 2', $result->collection);
    }
}