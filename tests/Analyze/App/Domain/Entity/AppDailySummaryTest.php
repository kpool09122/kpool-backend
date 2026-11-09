<?php

declare(strict_types=1);

namespace Tests\Analyze\App\Domain\Entity;

use PHPUnit\Framework\TestCase;
use Source\Analyze\App\Domain\Entity\AppDailySummary;
use Source\Analyze\App\Domain\Exception\InvalidDailySummaryException;
use Source\Analyze\App\Domain\ValueObject\AppDailySummaryIdentifier;
use Source\Analyze\Shared\Domain\ValueObject\RegionalMetricObservation;
use Source\Analyze\Shared\Domain\ValueObject\UtcDate;
use Tests\Helper\AnalyzeAppMetrics;

class AppDailySummaryTest extends TestCase
{
    public function testInvalidRegionalReplacementPreservesTheCurrentSnapshot(): void
    {
        $day = new UtcDate('2020-01-01');
        $summary = new AppDailySummary(new AppDailySummaryIdentifier('69200000-0000-7000-8000-000000000099'), $day, ...AnalyzeAppMetrics::summaryValues($day));
        $row = new RegionalMetricObservation('JP', 'JP-13', 'application_users', '7');
        $summary->replaceAnalysis($summary->applicationUsers(), $summary->registeredUsers(), $summary->countries(), [$row]);
        $original = [$summary->applicationUsers(), $summary->registeredUsers(), $summary->countries()];
        $replacement = AnalyzeAppMetrics::summaryValues($day, ['application_users' => '99']);
        foreach ([[$row, $row], [new RegionalMetricObservation('JP', 'JP-13', 'views', '7', wikiIdentifier: '69200000-0000-7000-8000-000000000001', language: 'ja')]] as $invalid) {
            try {
                $summary->replaceAnalysis(...$replacement, regions: $invalid);
                self::fail('Invalid regional ownership or duplicate grain must be rejected.');
            } catch (InvalidDailySummaryException) {
                self::assertSame([$row], $summary->regions());
                self::assertSame($original, [$summary->applicationUsers(), $summary->registeredUsers(), $summary->countries()]);
            }
        }
        $summary->replaceAnalysis($summary->applicationUsers(), $summary->registeredUsers(), $summary->countries(), []);
        self::assertSame([], $summary->regions());
    }

    public function testAllAnalyticalValuesExistAtConstructionAndAreReplacedTogether(): void
    {
        $day = new UtcDate('2020-01-01');
        $values = AnalyzeAppMetrics::summaryValues($day);
        $summary = new AppDailySummary(new AppDailySummaryIdentifier('69200000-0000-7000-8000-000000000099'), $day, $values[0], $values[1], $values[2]);
        self::assertSame($values[0], $summary->applicationUsers());
        self::assertSame($values[1], $summary->registeredUsers());
        $identifier = $summary->identifier();
        $updated = AnalyzeAppMetrics::summaryValues($day, ['application_users' => '0']);
        $summary->replaceAnalysis(...$updated, regions: $summary->regions());
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
            $summary->replaceAnalysis(...$invalid, regions: $summary->regions());
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
