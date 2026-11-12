<?php

declare(strict_types=1);

namespace Tests\Http\Client\GeminiClient;

use Application\Http\Client\GeminiClient\GeneratedHistoryEntries;
use PHPUnit\Framework\TestCase;

class GeneratedHistoryEntriesTest extends TestCase
{
    public function testReadsStructuredEntriesAndSkipsItemsWithoutAValidYearOrTitle(): void
    {
        $entries = GeneratedHistoryEntries::fromValue([
            ['title' => ' Undated Award ', 'detail' => 'Category', 'year' => null],
            ['title' => ' Festival (Special) ', 'detail' => ' Best Performance ', 'year' => 2024],
            ['title' => 'Missing year'],
            ['title' => 'Invalid year', 'year' => 24],
            ['title' => 'Year range', 'year' => '2024–2025'],
            ['title' => ' ', 'year' => 2024],
            'Old format (2025)',
            ['title' => 'Music Awards', 'detail' => null, 'year' => 2025],
        ]);

        self::assertSame([
            ['year' => 2024, 'title' => 'Festival (Special)', 'detail' => 'Best Performance'],
            ['year' => 2025, 'title' => 'Music Awards', 'detail' => null],
        ], $entries);
    }

    public function testReturnsNoEntriesWhenAllYearsAreUnknown(): void
    {
        self::assertSame([], GeneratedHistoryEntries::fromValue([
            ['title' => 'Undated Award', 'detail' => 'Category', 'year' => null],
        ]));
    }
}
