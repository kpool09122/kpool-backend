<?php

declare(strict_types=1);

namespace Tests\Analyze\Wiki\Application\UseCase\Command\ImportWikiDailySummary;

use LogicException;
use PHPUnit\Framework\TestCase;
use Source\Analyze\Wiki\Application\UseCase\Command\ImportWikiDailySummary\ImportWikiDailySummaryOutput;

class ImportWikiDailySummaryOutputTest extends TestCase
{
    public function testCompletedSummaryCountIsPreservedIncludingZero(): void
    {
        $output = new ImportWikiDailySummaryOutput();
        $output->setSummaryCount(0);
        self::assertSame(0, $output->summaryCount());
    }

    public function testUnexecutedOutputIsNotSuccess(): void
    {
        $this->expectException(LogicException::class);
        (new ImportWikiDailySummaryOutput())->summaryCount();
    }
}
