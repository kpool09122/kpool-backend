<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Domain\ValueObject\Block;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Wiki\Domain\ValueObject\Block\HistoryEntry;

class HistoryEntryTest extends TestCase
{
    public function testSerializesAnEntryWithNoDetails(): void
    {
        $entry = new HistoryEntry(2024, 'Debut');

        self::assertSame(['year' => 2024, 'title' => 'Debut', 'detail' => null], $entry->toArray());
    }

    #[DataProvider('invalidEntries')]
    public function testRejectsInvalidEntries(int $year, string $title): void
    {
        $this->expectException(InvalidArgumentException::class);

        new HistoryEntry($year, $title);
    }

    /** @return iterable<string, array{int, string}> */
    public static function invalidEntries(): iterable
    {
        yield 'three-digit year' => [999, 'Debut'];
        yield 'five-digit year' => [10000, 'Debut'];
        yield 'blank title' => [2024, ' '];
    }
}
