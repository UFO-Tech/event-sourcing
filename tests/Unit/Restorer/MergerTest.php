<?php

declare(strict_types = 1);

namespace Ufo\EventSourcing\Tests\Unit\Restorer;

use PHPUnit\Framework\TestCase;
use Ufo\EventSourcing\Restorer\Merger\Merger;
use Ufo\EventSourcing\Restorer\Merger\MergeContextDTO;

class MergerTest extends TestCase
{
    public function test_it_merges_changes_with_default_delete_placeholder(): void
    {
        $state = ['a' => 1, 'b' => 2, 'c' => 3];
        $changes = ['b' => '__DELETED__', 'c' => 5];

        $merger = new Merger();
        $result = $merger->merge($state, $changes);

        $this->assertSame(['a' => 1, 'c' => 5], $result);
    }

    public function test_it_merges_with_custom_delete_placeholder(): void
    {
        $state = ['a' => 1, 'b' => 2];
        $changes = ['b' => '__REMOVED__'];

        $merger = new Merger();
        $context = MergeContextDTO::create(deletePlaceholder: '__REMOVED__');

        $result = $merger->merge($state, $changes, $context);

        $this->assertSame(['a' => 1], $result);
    }

    public function test_it_recursively_merges_nested_structures(): void
    {
        $state = [
            'product' => [
                'price' => 100,
                'attributes' => ['color' => 'red', 'size' => 'M']
            ]
        ];

        $changes = [
            'product' => [
                'price' => 150,
                'attributes' => ['color' => 'blue']
            ]
        ];

        $merger = new Merger();
        $result = $merger->merge($state, $changes);

        $this->assertSame([
            'product' => [
                'price' => 150,
                'attributes' => ['color' => 'blue', 'size' => 'M'],
            ]
        ], $result);
    }

    public function test_it_handles_deleted_and_then_readded_keys(): void
    {
        $state = [
            'a' => ['id' => 1, 'val' => 100],
            'b' => ['id' => 2, 'val' => 200],
        ];

        $firstChanges = [
            'a' => '__DELETED__',
        ];

        $merger = new Merger();
        $afterFirstMerge = $merger->merge($state, $firstChanges);

        $this->assertSame([
            'b' => ['id' => 2, 'val' => 200],
        ], $afterFirstMerge);

        $secondChanges = [
            'a' => ['id' => 1, 'val' => 500],
        ];

        $finalState = $merger->merge($afterFirstMerge, $secondChanges);

        $this->assertSame([
            'b' => ['id' => 2, 'val' => 200],
            'a' => ['id' => 1, 'val' => 500],
        ], $finalState);
    }
}