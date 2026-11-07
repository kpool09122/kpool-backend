<?php

declare(strict_types=1);

namespace Tests\Analyze\Wiki\Infrastructure\Query;

use Application\Models\Analyze\Wiki\WikiDailySummary as SummaryModel;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
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
use Tests\Helper\AnalyzeSourceRecords;
use Tests\Helper\AnalyzeWikiMetrics;
use Tests\TestCase;

#[Group('useDb')]
class GetWikiDailySummaryReportTest extends TestCase
{
    public function testReturnsAllMetricsTogetherAndKeepsTheirAvailabilityIndependent(): void
    {
        $first = new UtcDate('2020-01-01');
        $this->save($first, ['wiki_views' => '2', 'wiki_viewers' => '3', 'wiki_engagement_seconds' => '4']);
        AnalyzeSourceRecords::history('draft_status_change', '2020-01-02');
        AnalyzeSourceRecords::history('publish', '2020-01-02');
        DB::flushQueryLog();
        DB::enableQueryLog();

        try {
            $report = (new GetWikiDailySummaryReport())->process(new GetWikiDailySummaryReportInput(new ReportingPeriod($first, $first->next(), AnalyzeWikiMetrics::now())));
            self::assertCount(2, DB::getQueryLog());
        } finally {
            DB::disableQueryLog();
            DB::flushQueryLog();
        }
        self::assertSame('2.000000000', $report->views()->total());
        self::assertSame('3.000000000', $report->viewers()->total());
        self::assertSame('4.000000000', $report->engagementTime()->total());
        self::assertSame(3, $report->engagementTime()->denominator());
        self::assertSame('1', $report->submissions()->total());
        self::assertSame('1', $report->submissions()->actorCount());
        self::assertSame('1', $report->publications()->total());
        self::assertSame('1', $report->publications()->actorCount());
        self::assertSame('missing', $report->views()->days()[1]['availability']);
        self::assertSame('measured', $report->submissions()->days()[1]['availability']);
    }

    public function testBatchAggregationKeepsMetricQualityAndUnknownDenominatorsIndependent(): void
    {
        $day = new UtcDate('2020-01-01');
        $this->save($day);
        $otherWiki = '69200000-0000-7000-8000-000000000015';
        $this->save($day, wiki: $otherWiki);
        foreach (SummaryModel::query()->get() as $row) {
            $measurements = $row->measurements;
            if ($row->wiki_identifier === $otherWiki) {
                $measurements[Metric::VIEWS->value]['version'] = '2';
                $measurements[Metric::VIEWS->value]['quality'] = ['excluded_definition'];
                $row->engagement_denominator = null;
            } else {
                $measurements[Metric::VIEWS->value]['quality'] = ['sampled'];
            }
            $row->forceFill(['measurements' => $measurements])->save();
        }
        $report = (new GetWikiDailySummaryReport())->process(new GetWikiDailySummaryReportInput(new ReportingPeriod($day, $day, AnalyzeWikiMetrics::now())));
        self::assertSame('5.000000000', $report->views()->total());
        self::assertSame(['sampled'], $report->views()->days()[0]['quality']);
        self::assertSame('10.000000000', $report->viewers()->total());
        self::assertSame('10.000000000', $report->engagementTime()->total());
        self::assertNull($report->engagementTime()->denominator());
        self::assertNull($report->engagementTime()->days()[0]['denominator']);
    }

    public function testAllWikiCellsAndDaysSumIncludingExtendedViewers(): void
    {
        $first = new UtcDate('2020-01-01');
        foreach ([$first, $first->next()] as $day) {
            $this->save($day, ['wiki_views' => '2', 'wiki_viewers' => '1'], '69200000-0000-7000-8000-000000000014');
            $this->save($day, ['wiki_views' => '3', 'wiki_viewers' => '2'], '69200000-0000-7000-8000-000000000015', 'en', 'US', '69200000-0000-7000-8000-000000000003');
        }
        $period = new ReportingPeriod($first, $first->next(), AnalyzeWikiMetrics::now());
        $query = new GetWikiDailySummaryReport();
        self::assertSame('10.000000000', $query->process(new GetWikiDailySummaryReportInput($period))->views()->total());
        self::assertSame('6.000000000', $query->process(new GetWikiDailySummaryReportInput($period))->viewers()->total());
        self::assertSame('4.000000000', $query->process(new GetWikiDailySummaryReportInput($period, language: 'ja', country: 'JP'))->views()->total());
        self::assertSame('6.000000000', $query->process(new GetWikiDailySummaryReportInput($period, translationSetIdentifier: '69200000-0000-7000-8000-000000000003'))->views()->total());
    }

    public function testNoPersistedEntityDoesNotInventMeasuredZero(): void
    {
        $day = new UtcDate('2020-01-01');
        $report = (new GetWikiDailySummaryReport())->process(new GetWikiDailySummaryReportInput(new ReportingPeriod($day, $day, AnalyzeWikiMetrics::now()), wikiIdentifier: '69200000-0000-7000-8000-000000000099'))->views();
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
        $report = (new GetWikiDailySummaryReport())->process(new GetWikiDailySummaryReportInput(new ReportingPeriod($first, $first->next(), AnalyzeWikiMetrics::now())))->engagementTime();
        self::assertSame('20.123456790', $report->total());
        self::assertSame(11, $report->denominator());
    }

    public function testUnknownAndMeasuredZeroRemainDistinct(): void
    {
        $first = new UtcDate('2020-01-01');
        $definition = AnalyzeWikiMetrics::definitions()[0];
        $this->save($first, ['wiki_views' => '0']);
        $report = (new GetWikiDailySummaryReport())->process(new GetWikiDailySummaryReportInput(new ReportingPeriod($first, $first->next(), AnalyzeWikiMetrics::now())))->views();
        self::assertSame(['0.000000000', null], array_column($report->days(), 'value'));
        self::assertSame(['measured', 'missing'], array_column($report->days(), 'availability'));
    }

    public function testSourceAndDefinitionVersionsNeverMix(): void
    {
        $day = new UtcDate('2020-01-01');
        $factory = new WikiDailySummaryFactory(new UuidGenerator());
        $wikiDailySummaryRepository = new WikiDailySummaryRepository();
        foreach ([['ga4', '1', '2'], ['ga4', '2', '3'], ['other', '1', '7']] as $index => [$source, $version, $count]) {
            $targetDay = new UtcDate('2020-01-0' . ($index + 1));
            $definition = new MetricDefinition($source, Metric::VIEWS, 'wiki_language_country', $version);
            $values = AnalyzeWikiMetrics::analysisValues($targetDay);
            $values[0] = new WikiViewCount($count, new MetricProvenance($definition, $targetDay, AnalyzeWikiMetrics::now()));
            $wikiDailySummaryRepository->save($factory->create(new WikiIdentifier('69200000-0000-7000-8000-000000000001'), new TranslationSetIdentifier('69200000-0000-7000-8000-000000000002'), Language::JAPANESE, CountryCode::JAPAN, $values[0], $values[1], $values[2], $values[3], $values[4]));
        }
        $report = (new GetWikiDailySummaryReport())->process(new GetWikiDailySummaryReportInput(new ReportingPeriod($day, new UtcDate('2020-01-03'), AnalyzeWikiMetrics::now())))->views();
        self::assertSame('2.000000000', $report->total());
    }

    public function testReportRevisionTracksItsOwnSummaryRows(): void
    {
        $day = new UtcDate('2020-01-01');
        $this->save($day);
        $input = new GetWikiDailySummaryReportInput(new ReportingPeriod($day, $day, AnalyzeWikiMetrics::now()));
        $query = new GetWikiDailySummaryReport();
        $revision = $query->process($input)->views()->revision();
        self::assertNotNull($revision);
        self::assertSame($revision, $query->process($input)->views()->revision());
        $this->save($day->next());
        self::assertSame($revision, $query->process($input)->views()->revision());
        $repository = new WikiDailySummaryRepository();
        $summary = $repository->findAllByDay($day)[0];
        self::assertNotNull($summary);
        $values = AnalyzeWikiMetrics::analysisValues($day, ['wiki_views' => '9']);
        $summary->replaceAnalysis(...$values);
        $repository->save($summary);
        self::assertNotSame($revision, $query->process($input)->views()->revision());
        self::assertSame('9.000000000', $query->process($input)->views()->total());
    }

    #[DataProvider('differentDefinitions')]
    public function testDifferentStoredDefinitionValuesAreMissing(MetricDefinition $definition): void
    {
        $day = new UtcDate('2020-01-01');
        $this->save($day);
        $row = SummaryModel::query()->firstOrFail();
        $measurements = $row->measurements;
        $measurements[$definition->metric->value]['source'] = $definition->source;
        $measurements[$definition->metric->value]['version'] = $definition->version;
        $row->forceFill(['measurements' => $measurements])->save();
        $report = (new GetWikiDailySummaryReport())->process(new GetWikiDailySummaryReportInput(new ReportingPeriod($day, $day, AnalyzeWikiMetrics::now())))->views();
        self::assertNull($report->total());
        self::assertNull($report->revision());
        self::assertSame('missing', $report->days()[0]['availability']);
    }

    /** @return array<string, array{MetricDefinition}> */
    public static function differentDefinitions(): array
    {
        return [
            'source' => [new MetricDefinition('other', Metric::VIEWS, 'wiki_language_country', '1')],
            'version' => [new MetricDefinition('ga4', Metric::VIEWS, 'wiki_language_country', '2')],
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
        $report = (new GetWikiDailySummaryReport())->process(new GetWikiDailySummaryReportInput(new ReportingPeriod($day, $day, $now)))->views();
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
        $input = new GetWikiDailySummaryReportInput(new ReportingPeriod($day, $day, AnalyzeWikiMetrics::now()));
        self::assertSame('9.000000000', (new GetWikiDailySummaryReport())->process($input)->views()->total());
    }

    public function testReadModelDoesNotRequireEntityProvenanceReconstruction(): void
    {
        $day = new UtcDate('2020-01-01');
        $this->save($day);
        $row = SummaryModel::query()->firstOrFail();
        $measurements = $row->measurements;
        $measurements[Metric::VIEWS->value]['updated_at'] = 'not-a-domain-timestamp';
        $row->forceFill(['measurements' => $measurements])->save();
        $report = (new GetWikiDailySummaryReport())->process(new GetWikiDailySummaryReportInput(new ReportingPeriod($day, $day, AnalyzeWikiMetrics::now())))->views();
        self::assertSame('5.000000000', $report->total());
        self::assertSame('measured', $report->days()[0]['availability']);
        self::assertNotNull($report->days()[0]['updatedAt']);
    }
}
