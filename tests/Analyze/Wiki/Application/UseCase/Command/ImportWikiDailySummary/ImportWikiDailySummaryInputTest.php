<?php

declare(strict_types=1);

namespace Tests\Analyze\Wiki\Application\UseCase\Command\ImportWikiDailySummary;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Source\Analyze\Shared\Domain\ValueObject\UtcDate;
use Source\Analyze\Wiki\Application\UseCase\Command\ImportWikiDailySummary\ImportWikiDailySummaryInput;
use Tests\Helper\AnalyzeWikiMetrics;

class ImportWikiDailySummaryInputTest extends TestCase
{
    public function testInputPreservesExactObjectsAndPageLimit(): void
    {
        $day = new UtcDate('2020-01-01');
        $now = AnalyzeWikiMetrics::now();
        $input = new ImportWikiDailySummaryInput($day, $now, 25);
        self::assertSame($day, $input->day());
        self::assertSame($now, $input->now());
        self::assertSame(25, $input->maxPages());
    }

    /** @return iterable<string, array{int}> */
    public static function invalidLimits(): iterable
    {
        yield 'zero' => [0];
        yield 'excess' => [100001];
    }

    #[DataProvider('invalidLimits')]
    public function testInvalidPageLimitIsRejected(int $limit): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ImportWikiDailySummaryInput(new UtcDate('2020-01-01'), AnalyzeWikiMetrics::now(), $limit);
    }
}
