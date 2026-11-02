<?php

declare(strict_types=1);

namespace Tests\Analyze\App\Application\UseCase\Command\ImportAppDailySummary;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Source\Analyze\App\Application\UseCase\Command\ImportAppDailySummary\ImportAppDailySummaryInput;
use Source\Analyze\Shared\Domain\ValueObject\UtcDate;
use Tests\Helper\AnalyzeAppMetrics;

class ImportAppDailySummaryInputTest extends TestCase
{
    public function testInputPreservesExactObjectsAndPageLimit(): void
    {
        $day = new UtcDate('2020-01-01');
        $now = AnalyzeAppMetrics::now();
        $input = new ImportAppDailySummaryInput($day, $now, 25);
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
        new ImportAppDailySummaryInput(new UtcDate('2020-01-01'), AnalyzeAppMetrics::now(), $limit);
    }
}
