<?php

declare(strict_types=1);

namespace Tests\Analyze\Shared\Infrastructure\Service;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Analyze\Shared\Application\UseCase\Query\MetricReportReadModel;
use Source\Analyze\Shared\Domain\ValueObject\Metric;
use Source\Analyze\Shared\Domain\ValueObject\MetricDefinition;
use Source\Analyze\Shared\Domain\ValueObject\ReportingPeriod;
use Source\Analyze\Shared\Domain\ValueObject\ReportingPreviousPeriod;
use Source\Analyze\Shared\Domain\ValueObject\UtcDate;
use Source\Analyze\Shared\Infrastructure\Service\PeriodComparisonCalculator;

class PeriodComparisonCalculatorTest extends TestCase
{
    public function testComparesTotalsRatherThanEndpointDaysWithExactDecimalRounding(): void
    {
        $current = $this->report('9907919180215092300.000000000');
        $previous = $this->report('9007199254740993000.000000000');
        self::assertSame('10.0', $this->compare($current, $previous)['percentageChange']);
        self::assertSame('-100.0', $this->compare($this->report('0'), $previous)['percentageChange']);
        self::assertSame('33.3', $this->compare($this->report('4'), $this->report('3'))['percentageChange']);
        self::assertSame('-0.1', $this->compare($this->report('1999'), $this->report('2000'))['percentageChange']);
    }

    public function testEngagementComparesWeightedPeriodAverages(): void
    {
        $current = $this->report('150', Metric::ENGAGEMENT_SECONDS, 5);
        $previous = $this->report('100', Metric::ENGAGEMENT_SECONDS, 10);
        self::assertSame('200.0', $this->compare($current, $previous)['percentageChange']);
        self::assertSame('invalid_denominator', $this->compare($this->report('150', Metric::ENGAGEMENT_SECONDS, 0), $previous)['reason']);
    }

    public function testDistinctActorCountIsComparedSeparately(): void
    {
        $current = $this->report('10', Metric::SUBMISSIONS, actors: '3');
        $previous = $this->report('20', Metric::SUBMISSIONS, actors: '2');
        $comparison = $this->compare($current, $previous);
        self::assertSame('-50.0', $comparison['percentageChange']);
        self::assertSame('50.0', $comparison['actorPercentageChange']);
        self::assertNull($comparison['actorReason']);
    }

    public function testPartialAndMissingDaysDoNotDiscardAvailablePeriodAggregates(): void
    {
        $valid = $this->report('10');
        foreach (['not_collected', 'missing', 'incomplete'] as $state) {
            self::assertSame('-50.0', $this->compare($valid, $this->report('20', state: $state))['percentageChange']);
            self::assertSame('100.0', $this->compare($this->report('20', state: $state), $valid)['percentageChange']);
        }
        self::assertSame('-50.0', $this->compare($valid, $this->report('20', quality: ['thresholding']))['percentageChange']);
        self::assertSame('200.0', $this->compare($this->report('150', Metric::ENGAGEMENT_SECONDS, 5, state: 'incomplete'), $this->report('100', Metric::ENGAGEMENT_SECONDS, 10))['percentageChange']);
    }

    public function testDistinguishesMissingPreviousDataFromMissingCurrentDataAndMeasuredZero(): void
    {
        $valid = $this->report('10');
        self::assertSame('no_previous_data', $this->compare($valid, $this->report(null))['reason']);
        self::assertSame('no_current_data', $this->compare($this->report(null), $valid)['reason']);
        self::assertSame('no_previous_data', $this->compare($this->report(null), $this->report(null))['reason']);
        $zero = $this->compare($valid, $this->report('0'));
        self::assertNull($zero['percentageChange']);
        self::assertSame('zero_baseline', $zero['reason']);
        self::assertSame('-100.0', $this->compare($this->report('0'), $valid)['percentageChange']);
    }

    public function testIncludesComparisonDatesAndKeepsCurrentReportValues(): void
    {
        $current = $this->report('20');
        $data = (new PeriodComparisonCalculator())->calculate($current, $this->report('10'), $this->previousPeriod())->toArray();
        self::assertSame('20', $current->total());
        self::assertSame('2019-12-30', $data['from']);
        self::assertSame('2019-12-31', $data['to']);
        self::assertSame('100.0', $data['percentageChange']);
        self::assertNull($current->comparison());
    }

    public function testSnapshotComparisonOnlyRequiresEachPeriodsClosingBalance(): void
    {
        $current = $this->report('120', Metric::REGISTERED_USERS);
        $previous = $this->report('100', Metric::REGISTERED_USERS);
        self::assertSame('20.0', $this->compare($current, $previous)['percentageChange']);
    }

    /** @param list<string> $quality */
    private function report(?string $value, Metric $metric = Metric::VIEWS, ?int $denominator = null, ?string $actors = null, string $state = 'measured', array $quality = []): MetricReportReadModel
    {
        $days = [];
        foreach (['2020-01-01', '2020-01-02'] as $day) {
            $days[] = ['day' => $day, 'availability' => $state, 'value' => $value, 'denominator' => $denominator, 'quality' => $quality, 'updatedAt' => null];
        }

        return new MetricReportReadModel(new MetricDefinition('ga4', $metric, ($metric->isSnapshot() ? 'application_day' : 'wiki_language_country'), '1'), null, $days, $value, $denominator, $actors);
    }

    private function previousPeriod(): ReportingPreviousPeriod
    {
        return new ReportingPreviousPeriod(new ReportingPeriod(new UtcDate('2020-01-01'), new UtcDate('2020-01-02'), new DateTimeImmutable('2026-11-10T00:00:00Z')));
    }

    /** @return array<string, mixed> */
    private function compare(MetricReportReadModel $current, MetricReportReadModel $previous): array
    {
        return (new PeriodComparisonCalculator())->calculate($current, $previous, $this->previousPeriod())->toArray();
    }
}
