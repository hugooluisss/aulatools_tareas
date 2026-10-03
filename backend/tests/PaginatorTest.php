<?php

declare(strict_types=1);

namespace App\Tests;

use App\Shared\Paginator;
use PHPUnit\Framework\TestCase;

final class PaginatorTest extends TestCase
{
    public function testParsingBoundsAndBuildingEmptyOutOfRangePage(): void
    {
        self::assertSame(['page' => 1, 'perPage' => 20, 'offset' => 0], Paginator::parse([]));
        self::assertSame(['page' => 2, 'perPage' => 2, 'offset' => 2], Paginator::parse(['page' => '2', 'per_page' => '2']));
        foreach ([['page' => 0], ['per_page' => 101]] as $query) {
            try {
                Paginator::parse($query);
                self::fail('Expected invalid pagination.');
            } catch (\InvalidArgumentException $exception) {
                self::assertSame('Invalid pagination.', $exception->getMessage());
            }
        }
        self::assertSame(['items' => [], 'page' => 99, 'per_page' => 20, 'total' => 143, 'total_pages' => 8], Paginator::build([], 143, 99, 20));
    }
}
