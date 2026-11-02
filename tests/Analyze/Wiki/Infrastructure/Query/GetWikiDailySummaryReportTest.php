<?php

declare(strict_types=1);

namespace Tests\Analyze\Wiki\Infrastructure\Query;

use Application\Models\Analyze\Wiki\WikiDailySummary as SummaryModel;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Source\Analyze\Shared\Domain\Exception\MetricDefinitionMismatchException;
use Source\Analyze\Shared\Domain\ValueObject\Metric;
use Source\Analyze\Shared\Domain\ValueObject\MetricDefinition;
use Source\Analyze\Shared\Domain\ValueObject\MetricProvenance;
use Source\Analyze\Shared\Domain\ValueObject\ReportingPeriod;
use Source\Analyze\Shared\Domain\ValueObject\UtcDate;
use Source\Analyze\Wiki\Application\UseCase\Query\GetWikiDailySummaryReport\GetWikiDailySummaryReportInput;
use Source\Analyze\Wiki\Domain\ValueObject\WikiEngagementTime;
use Source\Analyze\Wiki\Domain\ValueObject\WikiViewCount;
use Source\Analyze\Wiki\Domain\ValueObject\WikiViewerCount;
use Source\Analyze\Wiki\Infrastructure\Factory\WikiDailySummaryFactory;
use Source\Analyze\Wiki\Infrastructure\Query\GetWikiDailySummaryReport;
use Source\Analyze\Wiki\Infrastructure\Repository\WikiDailySummaryRepository;
use Source\Shared\Domain\ValueObject\CountryCode;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Shared\Domain\ValueObject\WikiIdentifier;
use Source\Shared\Infrastructure\Service\Uuid\UuidGenerator;
use Tests\Helper\AnalyzeWikiMetrics;
use Tests\TestCase;

#[Group('useDb')]
class GetWikiDailySummaryReportTest extends TestCase
{
    public function testAllWikiCellsAndDaysSumIncludingExtendedViewers(): void
    {
        $first = new UtcDate('2020-01-01');
        foreach ([$first, $first->next()] as $day) {
            $this->save($day, ['wiki_views' => '2', 'wiki_viewers' => '1'], '69200000-0000-7000-8000-000000000014');
            $this->save($day, ['wiki_views' => '3', 'wiki_viewers' => '2'], '69200000-0000-7000-8000-000000000015', 'en', 'US', '69200000-0000-7000-8000-000000000003');
        }
        $period = new ReportingPeriod($first, $first->next(), AnalyzeWikiMetrics::now());
        $query = new GetWikiDailySummaryReport();
        self::assertSame('10.000000000', $query->process(new GetWikiDailySummaryReportInput(AnalyzeWikiMetrics::definitions()[0], $period))->total());
        self::assertSame('6.000000000', $query->process(new GetWikiDailySummaryReportInput(AnalyzeWikiMetrics::definitions()[1], $period))->total());
        self::assertSame('4.000000000', $query->process(new GetWikiDailySummaryReportInput(AnalyzeWikiMetrics::definitions()[0], $period, language: 'ja', country: 'JP'))->total());
        self::assertSame('6.000000000', $query->process(new GetWikiDailySummaryReportInput(AnalyzeWikiMetrics::definitions()[0], $period, translationSetIdentifier: '69200000-0000-7000-8000-000000000003'))->total());
    }

    public function testNoPersistedEntityDoesNotInventMeasuredZero(): void
    {
        $day = new UtcDate('2020-01-01');
        $report = (new GetWikiDailySummaryReport())->process(new GetWikiDailySummaryReportInput(AnalyzeWikiMetrics::definitions()[0], new ReportingPeriod($day, $day, AnalyzeWikiMetrics::now()), wikiIdentifier: 'absent'));
        self::assertNull($report->total());
        self::assertSame('missing', $report->days()[0]['availability']);
        self::assertNull($report->days()[0]['value']);
    }

    public function testEngagementTimeRetainsPrecisionAndSumsItsSampleCounts(): void
    {
        $first = new UtcDate('2020-01-01');
        $definition = AnalyzeWikiMetrics::definitions()[2];
        foreach ([[$first, '0.123456789', 1], [$first->next(), '20.000000001', 10]] as [$day, $seconds, $sampleCount]) {
            $values = AnalyzeWikiMetrics::analysisValues($day);
            $values[2] = new WikiEngagementTime($seconds, $sampleCount, new MetricProvenance($definition, $day, AnalyzeWikiMetrics::now()));
            $summary = (new WikiDailySummaryFactory(new UuidGenerator()))->create(new WikiIdentifier('69200000-0000-7000-8000-000000000001'), new TranslationSetIdentifier('69200000-0000-7000-8000-000000000002'), Language::JAPANESE, CountryCode::JAPAN, $values[0], $values[1], $values[2], $values[3], $values[4]);
            (new WikiDailySummaryRepository())->save($summary);
        }
        $report = (new GetWikiDailySummaryReport())->process(new GetWikiDailySummaryReportInput($definition, new ReportingPeriod($first, $first->next(), AnalyzeWikiMetrics::now())));
        self::assertSame('20.123456790', $report->total());
        self::assertSame(11, $report->denominator());
    }

    public function testUnknownAndMeasuredZeroRemainDistinct(): void
    {
        $first = new UtcDate('2020-01-01');
        $definition = AnalyzeWikiMetrics::definitions()[0];
        $this->save($first, ['wiki_views' => '0']);
        $report = (new GetWikiDailySummaryReport())->process(new GetWikiDailySummaryReportInput($definition, new ReportingPeriod($first, $first->next(), AnalyzeWikiMetrics::now())));
        self::assertSame(['0.000000000', null], array_column($report->days(), 'value'));
        self::assertSame(['measured', 'missing'], array_column($report->days(), 'availability'));
    }

    public function testSourceAndDefinitionVersionsNeverMix(): void
    {
        $day = new UtcDate('2020-01-01');
        $factory = new WikiDailySummaryFactory(new UuidGenerator());
        $wikiDailySummaryRepository = new WikiDailySummaryRepository();
        foreach ([['a', '1', '2'], ['a', '2', '3'], ['b', '1', '7']] as $index => [$source, $version, $count]) {
            $targetDay = new UtcDate('2020-01-0' . ($index + 1));
            $definition = new MetricDefinition($source, Metric::VIEWS, 'wiki_language_country', $version);
            $values = AnalyzeWikiMetrics::analysisValues($targetDay);
            $values[0] = new WikiViewCount($count, new MetricProvenance($definition, $targetDay, AnalyzeWikiMetrics::now()));
            $wikiDailySummaryRepository->save($factory->create(new WikiIdentifier('69200000-0000-7000-8000-000000000001'), new TranslationSetIdentifier('69200000-0000-7000-8000-000000000002'), Language::JAPANESE, CountryCode::JAPAN, $values[0], $values[1], $values[2], $values[3], $values[4]));
        }
        foreach ([['a', '1', '2'], ['a', '2', '3'], ['b', '1', '7']] as [$source, $version, $count]) {
            $definition = new MetricDefinition($source, Metric::VIEWS, 'wiki_language_country', $version);
            $report = (new GetWikiDailySummaryReport())->process(new GetWikiDailySummaryReportInput($definition, new ReportingPeriod($day, new UtcDate('2020-01-03'), AnalyzeWikiMetrics::now())));
            self::assertSame($count . '.000000000', $report->total());
        }
    }

    public function testReportRevisionTracksItsOwnSummaryRows(): void
    {
        $day = new UtcDate('2020-01-01');
        $this->save($day);
        $input = new GetWikiDailySummaryReportInput(AnalyzeWikiMetrics::definitions()[0], new ReportingPeriod($day, $day, AnalyzeWikiMetrics::now()));
        $query = new GetWikiDailySummaryReport();
        $revision = $query->process($input)->revision();
        self::assertNotNull($revision);
        self::assertSame($revision, $query->process($input)->revision());
        $this->save($day->next());
        self::assertSame($revision, $query->process($input)->revision());
        $repository = new WikiDailySummaryRepository();
        $summary = $repository->findAllByDay($day)[0];
        self::assertNotNull($summary);
        $values = AnalyzeWikiMetrics::analysisValues($day, ['wiki_views' => '9']);
        $summary->replaceAnalysis(...$values);
        $repository->save($summary);
        self::assertNotSame($revision, $query->process($input)->revision());
        self::assertSame('9.000000000', $query->process($input)->total());
    }

    #[DataProvider('differentDefinitions')]
    public function testDifferentDefinitionValuesAreMissing(MetricDefinition $definition): void
    {
        $day = new UtcDate('2020-01-01');
        $this->save($day);
        $report = (new GetWikiDailySummaryReport())->process(new GetWikiDailySummaryReportInput($definition, new ReportingPeriod($day, $day, AnalyzeWikiMetrics::now())));
        self::assertNull($report->total());
        self::assertNull($report->revision());
        self::assertSame('missing', $report->days()[0]['availability']);
    }

    /** @return array<string, array{MetricDefinition}> */
    public static function differentDefinitions(): array
    {
        return [
            'source' => [new MetricDefinition('other', Metric::VIEWS, 'wiki_language_country', '1')],
            'version' => [new MetricDefinition('test', Metric::VIEWS, 'wiki_language_country', '2')],
        ];
    }

    /** @param array<string, string> $overrides */
    private function save(UtcDate $day, array $overrides = [], string $wiki = '69200000-0000-7000-8000-000000000001', string $language = 'ja', string $country = 'JP', string $translationSet = '69200000-0000-7000-8000-000000000002'): void
    {
        $values = AnalyzeWikiMetrics::analysisValues($day, $overrides);
        $summary = (new WikiDailySummaryFactory(new UuidGenerator()))->create(new WikiIdentifier($wiki), new TranslationSetIdentifier($translationSet), Language::from($language), $country === '' ? null : CountryCode::from($country), $values[0], $values[1], $values[2], $values[3], $values[4]);
        (new WikiDailySummaryRepository())->save($summary);
    }

    public function testReportReadsAnalyticalSummaryWithoutAnImportCheckpoint(): void
    {
        $definition = AnalyzeWikiMetrics::definition();
        $day = new UtcDate('2020-01-01');
        $now = AnalyzeWikiMetrics::now();
        $values = AnalyzeWikiMetrics::analysisValues($day);
        $values[0] = new WikiViewCount('5', new MetricProvenance($definition, $day, $now, ['sampled']));
        $summary = (new WikiDailySummaryFactory(new UuidGenerator()))->create(new WikiIdentifier('69200000-0000-7000-8000-000000000001'), new TranslationSetIdentifier('69200000-0000-7000-8000-000000000002'), Language::JAPANESE, CountryCode::JAPAN, $values[0], $values[1], $values[2], $values[3], $values[4]);
        (new WikiDailySummaryRepository())->save($summary);
        $report = (new GetWikiDailySummaryReport())->process(new GetWikiDailySummaryReportInput($definition, new ReportingPeriod($day, $day, $now)));
        self::assertSame('5.000000000', $report->total());
        self::assertSame('5.000000000', $report->days()[0]['value']);
        self::assertSame('measured', $report->days()[0]['availability']);
        self::assertSame(['sampled'], $report->days()[0]['quality']);
    }

    public function testReplacingOtherMetricMetadataRetainsOneDailySummary(): void
    {
        $day = new UtcDate('2020-01-01');
        $values = AnalyzeWikiMetrics::analysisValues($day);
        $definition = $values[0]->provenance()->definition();
        $factory = new WikiDailySummaryFactory(new UuidGenerator());
        $wikiDailySummaryRepository = new WikiDailySummaryRepository();
        $wikiDailySummaryRepository->save($factory->create(new WikiIdentifier('69200000-0000-7000-8000-000000000001'), new TranslationSetIdentifier('69200000-0000-7000-8000-000000000002'), Language::JAPANESE, CountryCode::JAPAN, $values[0], $values[1], $values[2], $values[3], $values[4]));
        $other = AnalyzeWikiMetrics::definition(Metric::VIEWERS, 'alternate');
        $values[0] = new WikiViewCount('9', new MetricProvenance($definition, $day, AnalyzeWikiMetrics::now()));
        $values[1] = new WikiViewerCount('11', new MetricProvenance($other, $day, AnalyzeWikiMetrics::now()));
        $summary = $wikiDailySummaryRepository->findAllByDay($day)[0];
        $summary->replaceAnalysis($values[0], $values[1], $values[2], $values[3], $values[4]);
        $wikiDailySummaryRepository->save($summary);
        $input = new GetWikiDailySummaryReportInput($definition, new ReportingPeriod($day, $day, AnalyzeWikiMetrics::now()));
        self::assertSame('9.000000000', (new GetWikiDailySummaryReport())->process($input)->total());
    }

    public function testReadModelDoesNotRequireEntityProvenanceReconstruction(): void
    {
        $day = new UtcDate('2020-01-01');
        $this->save($day);
        $row = SummaryModel::query()->firstOrFail();
        $measurements = $row->measurements;
        $measurements[Metric::VIEWS->value]['updated_at'] = 'not-a-domain-timestamp';
        $row->forceFill(['measurements' => $measurements])->save();
        $report = (new GetWikiDailySummaryReport())->process(new GetWikiDailySummaryReportInput(AnalyzeWikiMetrics::definitions()[0], new ReportingPeriod($day, $day, AnalyzeWikiMetrics::now())));
        self::assertSame('5.000000000', $report->total());
        self::assertSame('measured', $report->days()[0]['availability']);
        self::assertNotNull($report->days()[0]['updatedAt']);
    }

    public function testRejectsMetricsOutsideItsSubdomain(): void
    {
        $definition = new MetricDefinition('ga4', Metric::APPLICATION_USERS, 'application_day', '1');
        $day = new UtcDate('2020-01-01');
        $input = new GetWikiDailySummaryReportInput($definition, new ReportingPeriod($day, $day, AnalyzeWikiMetrics::now()));
        $this->expectException(MetricDefinitionMismatchException::class);
        (new GetWikiDailySummaryReport())->process($input);
    }
}
