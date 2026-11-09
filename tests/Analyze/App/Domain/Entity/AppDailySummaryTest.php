<?php

declare(strict_types=1);

namespace Tests\Analyze\App\Domain\Entity;

use PHPUnit\Framework\TestCase;
use Source\Analyze\App\Domain\Entity\AppDailySummary;
use Source\Analyze\App\Domain\Exception\InvalidDailySummaryException;
use Source\Analyze\App\Domain\ValueObject\AppDailySummaryIdentifier;
use Source\Analyze\Shared\Domain\ValueObject\UtcDate;
use Tests\Helper\AnalyzeAppMetrics;

class AppDailySummaryTest extends TestCase
{
    public function testAllAnalyticalValuesExistAtConstructionAndAreReplacedTogether(): void
    {
        $day = new UtcDate('2020-01-01');
        $values = AnalyzeAppMetrics::summaryValues($day);
        $summary = new AppDailySummary(new AppDailySummaryIdentifier('69200000-0000-7000-8000-000000000099'), $day, $values[0], $values[1], $values[2]);
        self::assertSame($values[0], $summary->applicationUsers());
        self::assertSame($values[1], $summary->registeredUsers());
        $identifier = $summary->identifier();
        $updated = AnalyzeAppMetrics::summaryValues($day, ['application_users' => '0']);
        $summary->replaceAnalysis(...$updated);
        self::assertSame($identifier, $summary->identifier());
        self::assertSame('0', $summary->applicationUsers()->count());
    }

    public function testInvalidReplacementDoesNotMutateAnyValue(): void
    {
        $day = new UtcDate('2020-01-01');
        $values = AnalyzeAppMetrics::summaryValues($day);
        $summary = new AppDailySummary(new AppDailySummaryIdentifier('69200000-0000-7000-8000-000000000099'), $day, $values[0], $values[1], $values[2]);
        $invalid = AnalyzeAppMetrics::summaryValues($day, ['application_users' => '9']);
        $invalid[1] = AnalyzeAppMetrics::summaryValues($day->next())[1];

        try {
            $summary->replaceAnalysis(...$invalid);
            self::fail('A mismatched day must be rejected.');
        } catch (InvalidDailySummaryException) {
            self::assertSame($values[0], $summary->applicationUsers());
            self::assertSame($values[1], $summary->registeredUsers());
        }
    }

    public function testInitialValuesMustBelongToTheSummaryDay(): void
    {
        $day = new UtcDate('2020-01-01');
        $values = AnalyzeAppMetrics::summaryValues($day);
        $values[0] = AnalyzeAppMetrics::summaryValues($day->next())[0];
        $this->expectException(InvalidDailySummaryException::class);
        new AppDailySummary(new AppDailySummaryIdentifier('69200000-0000-7000-8000-000000000099'), $day, $values[0], $values[1], $values[2]);
    }
}
