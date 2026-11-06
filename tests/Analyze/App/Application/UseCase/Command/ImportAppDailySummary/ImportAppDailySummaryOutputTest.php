<?php

declare(strict_types=1);

namespace Tests\Analyze\App\Application\UseCase\Command\ImportAppDailySummary;

use LogicException;
use PHPUnit\Framework\TestCase;
use Source\Analyze\App\Application\UseCase\Command\ImportAppDailySummary\ImportAppDailySummaryOutput;

class ImportAppDailySummaryOutputTest extends TestCase
{
    public function testCompletedSummaryCountIsPreservedIncludingZero(): void
    {
        $output = new ImportAppDailySummaryOutput();
        $output->setSummaryCount(0);
        self::assertSame(0, $output->summaryCount());
    }

    public function testUnexecutedOutputIsNotSuccess(): void
    {
        $this->expectException(LogicException::class);
        (new ImportAppDailySummaryOutput())->summaryCount();
    }
}
