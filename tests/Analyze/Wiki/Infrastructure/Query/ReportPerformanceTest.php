<?php

declare(strict_types=1);

namespace Tests\Analyze\Wiki\Infrastructure\Query;

use Application\Models\Analyze\Wiki\WikiDailySummary as SummaryModel;
use Illuminate\Support\Facades\DB;
use Override;
use PHPUnit\Framework\Attributes\Group;
use Source\Analyze\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\Analyze\Shared\Domain\ValueObject\ReportingPeriod;
use Source\Analyze\Shared\Domain\ValueObject\UtcDate;
use Source\Analyze\Wiki\Application\UseCase\Query\GetWikiDailySummaryReport\GetWikiDailySummaryReportInput;
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
use Tests\Helper\CreateAnalyzePrincipal;
use Tests\TestCase;

#[Group('useDb')]
#[Group('performance')]
class ReportPerformanceTest extends TestCase
{
    /** @phpstan-ignore property.uninitialized (setUp で初期化) */
    private PrincipalIdentifier $principalIdentifier;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->principalIdentifier = CreateAnalyzePrincipal::create()->principalIdentifier();
    }

    public function testMaximumYearAllTranslationSetAndCountryHaveBoundedQueries(): void
    {
        $day = new UtcDate('2020-01-01');
        $translationSet = '69200000-0000-7000-8000-000000000002';
        $summary = (new WikiDailySummaryFactory(new UuidGenerator()))->create(new WikiIdentifier('69200000-0000-7000-8000-000000000001'), new TranslationSetIdentifier($translationSet), Language::JAPANESE, CountryCode::JAPAN, ...AnalyzeWikiMetrics::analysisValues($day));
        (new WikiDailySummaryRepository())->save($summary);
        DB::statement(<<<'SQL'
            INSERT INTO analyze_wiki_daily_summaries
            SELECT (jsonb_populate_record(NULL::analyze_wiki_daily_summaries, to_jsonb(seed) || jsonb_build_object(
                'id', md5(concat(d, ':', w, ':', c))::uuid,
                'day', d::date, 'wiki_identifier', md5(w::text)::uuid, 'country', c,
                'translation_set_identifier', CASE WHEN w <= 50 THEN ?::uuid ELSE md5('other-set')::uuid END
            ))).*
            FROM analyze_wiki_daily_summaries seed
            CROSS JOIN generate_series('2020-01-01'::date, '2020-12-31'::date, '1 day') d
            CROSS JOIN generate_series(1, 100) w
            CROSS JOIN (VALUES (''), ('JP'), ('US'), ('KR')) countries(c)
            SQL, [$translationSet]);
        SummaryModel::query()->where('id', (string) $summary->identifier())->delete();
        $history = AnalyzeSourceRecords::history('publish', '2020-01-01');
        DB::statement(<<<'SQL'
            INSERT INTO wiki_histories
            SELECT (jsonb_populate_record(NULL::wiki_histories, to_jsonb(seed) || jsonb_build_object(
                'id', md5(concat('history-', n))::uuid, 'actor_id', md5((n % 1000)::text)::uuid,
                'recorded_at', '2020-01-01'::timestamp + (n % 366) * interval '1 day',
                'visitor_country', CASE WHEN n % 4 = 0 THEN NULL WHEN n % 4 = 1 THEN 'JP' WHEN n % 4 = 2 THEN 'US' ELSE 'KR' END
            ))).*
            FROM wiki_histories seed CROSS JOIN generate_series(1, 20000) n
            WHERE seed.id = ?::uuid
            SQL, [$history->id]);
        $history->delete();
        DB::statement('ANALYZE analyze_wiki_daily_summaries');
        DB::statement('ANALYZE wiki_histories');
        self::assertSame(146400, SummaryModel::query()->count());
        $period = new ReportingPeriod($day, new UtcDate('2020-12-31'), AnalyzeWikiMetrics::now());
        $results = [];
        foreach (['ALL' => new GetWikiDailySummaryReportInput($this->principalIdentifier, $period), 'translation-set' => new GetWikiDailySummaryReportInput($this->principalIdentifier, $period, translationSetIdentifier: $translationSet), 'country' => new GetWikiDailySummaryReportInput($this->principalIdentifier, $period, country: 'JP')] as $name => $input) {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $started = microtime(true);

            try {
                $report = ($this->app()->make(GetWikiDailySummaryReport::class))->process($input);
                $queries = DB::getQueryLog();
                self::assertCount(10, $queries);
                self::assertCount(366, $report->views()->days());
                self::assertNotNull($report->views()->total());
            } finally {
                DB::disableQueryLog();
            }
            $elapsed = microtime(true) - $started;
            self::assertLessThan(15.0, $elapsed, $name . ' report exceeded 15s');
            $plans = [];
            foreach ($queries as $query) {
                $plan = DB::select('EXPLAIN (ANALYZE, BUFFERS, FORMAT JSON) ' . $query['query'], $query['bindings']);
                $plans[] = $plan;
            }
            $results[$name] = ['seconds' => $elapsed, 'queryCount' => count($queries), 'plans' => $plans];
        }
        file_put_contents(__DIR__ . '/../../../../../storage/logs/analyze-report-performance.json', json_encode(['summaryRows' => 146400, 'historyRows' => 20000, 'results' => $results], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
    }
}
