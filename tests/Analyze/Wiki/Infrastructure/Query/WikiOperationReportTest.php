<?php

declare(strict_types=1);

namespace Tests\Analyze\Wiki\Infrastructure\Query;

use Application\Models\Wiki\DraftWiki;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Override;
use PHPUnit\Framework\Attributes\Group;
use Source\Analyze\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\Analyze\Shared\Application\UseCase\Query\MetricReportReadModel;
use Source\Analyze\Shared\Domain\ValueObject\Metric;
use Source\Analyze\Shared\Domain\ValueObject\ReportingPeriod;
use Source\Analyze\Shared\Domain\ValueObject\UtcDate;
use Source\Analyze\Shared\Infrastructure\Service\RegionalMetricsFetchService;
use Source\Analyze\Shared\Infrastructure\Service\RegionNormalizer;
use Source\Analyze\Wiki\Application\Service\DailyMetricsFetchServiceInterface;
use Source\Analyze\Wiki\Application\UseCase\Command\ImportWikiDailySummary\ImportWikiDailySummary;
use Source\Analyze\Wiki\Application\UseCase\Command\ImportWikiDailySummary\ImportWikiDailySummaryInput;
use Source\Analyze\Wiki\Application\UseCase\Command\ImportWikiDailySummary\ImportWikiDailySummaryOutput;
use Source\Analyze\Wiki\Application\UseCase\Query\GetWikiDailySummaryReport\GetWikiDailySummaryReportInput;
use Source\Analyze\Wiki\Infrastructure\Query\GetWikiDailySummaryReport;
use Source\Analyze\Wiki\Infrastructure\Service\DailyMetricsFetchService;
use Source\Analyze\Wiki\Infrastructure\Service\DailyWikiOperationsQueryService;
use Tests\Helper\AnalyticsHttpStub;
use Tests\Helper\AnalyzeSourceRecords;
use Tests\Helper\AnalyzeWikiMetrics;
use Tests\Helper\CreateAnalyzePrincipal;
use Tests\Helper\CreateDraftWiki;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class WikiOperationReportTest extends TestCase
{
    /** @phpstan-ignore property.uninitialized (setUp で初期化) */
    private PrincipalIdentifier $principalIdentifier;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->principalIdentifier = CreateAnalyzePrincipal::create()->principalIdentifier();
    }

    public function testUnpublishedSubmissionIsReportedWithoutImportAndRepeatedImportsDoNotDuplicateIt(): void
    {
        AnalyzeSourceRecords::history('draft_status_change', '2020-01-01', wiki: null, draft: StrTestHelper::generateUuid());
        self::assertSame('1', $this->report()->total());
        self::assertSame('1', $this->report()->actorCount());
        $this->import('2020-01-01');
        $this->import('2020-01-01');
        self::assertSame('1', $this->report()->total());
        self::assertSame('1', $this->report()->actorCount());
        self::assertSame('measured', $this->report()->days()[0]['availability']);
    }

    public function testActorsAreDistinctPrincipalsAcrossDaysAndWikisEvenWithTheSameIdentity(): void
    {
        $first = AnalyzeSourceRecords::history('draft_status_change', '2020-01-01');
        $identity = StrTestHelper::generateUuid();
        $first->update(['actor_identity_id' => $identity]);
        $second = AnalyzeSourceRecords::history('draft_status_change', '2020-01-02', wiki: StrTestHelper::generateUuid());
        $second->update(['actor_id' => $first->actor_id, 'actor_identity_id' => null]);
        $third = AnalyzeSourceRecords::history('draft_status_change', '2020-01-02');
        $third->update(['actor_identity_id' => $identity, 'submitter_id' => $first->actor_id]);
        $report = $this->report();
        self::assertSame('3', $report->total());
        self::assertSame('2', $report->actorCount());
        self::assertSame(['1', '2', '0'], array_column($report->days(), 'actorCount'));
    }

    public function testPublicationsCountTheActingPrincipalRatherThanSubmitterOrIdentity(): void
    {
        $first = AnalyzeSourceRecords::history('publish', '2020-01-01');
        $first->update(['submitter_id' => StrTestHelper::generateUuid()]);
        $second = AnalyzeSourceRecords::history('publish', '2020-01-02', 'US', wiki: StrTestHelper::generateUuid());
        $second->update(['actor_id' => $first->actor_id, 'submitter_id' => StrTestHelper::generateUuid()]);
        self::assertSame('2', $this->report(Metric::PUBLICATIONS)->total());
        self::assertSame('1', $this->report(Metric::PUBLICATIONS)->actorCount());
        self::assertSame('1', $this->report(Metric::PUBLICATIONS, country: 'US')->actorCount());
        self::assertSame('0', $this->report()->total());
    }

    public function testPublicationLinksEarlierSubmissionsWithoutChangingTheirCountOrActor(): void
    {
        $draft = StrTestHelper::generateUuid();
        $first = AnalyzeSourceRecords::history('draft_status_change', '2020-01-01', wiki: null, draft: $draft);
        $second = AnalyzeSourceRecords::history('draft_status_change', '2020-01-02', wiki: null, draft: $draft);
        $second->update(['actor_id' => $first->actor_id]);
        CreateDraftWiki::create($draft, 'group', ['translation_set_identifier' => $first->translation_set_identifier ?? '', 'language' => 'ja']);
        self::assertSame('2', $this->report()->total());
        $publication = AnalyzeSourceRecords::history('publish', '2020-01-03', draft: $draft);
        DraftWiki::query()->where('id', $draft)->delete();
        self::assertSame('2', $this->report(wiki: $publication->wiki_id)->total());
        self::assertSame('1', $this->report(wiki: $publication->wiki_id)->actorCount());
        self::assertSame('1', $this->report(Metric::PUBLICATIONS)->total());
        self::assertSame('1', $this->report(Metric::PUBLICATIONS)->actorCount());
    }

    public function testBundledReportUsesBoundedQueriesAndRepeatedPublicationsDoNotMultiplySubmissions(): void
    {
        $draft = StrTestHelper::generateUuid();
        $submission = AnalyzeSourceRecords::history('draft_status_change', '2020-01-01', wiki: null, draft: $draft);
        $first = AnalyzeSourceRecords::history('publish', '2020-01-02', draft: $draft);
        AnalyzeSourceRecords::history('publish', '2020-01-03', draft: $draft);
        DB::flushQueryLog();
        DB::enableQueryLog();

        try {
            $report = $this->report(wiki: $first->wiki_id);
            self::assertCount(12, DB::getQueryLog());
        } finally {
            DB::disableQueryLog();
            DB::flushQueryLog();
        }
        self::assertSame('1', $report->total());
        self::assertSame('1', $report->actorCount());
        self::assertSame('1', $this->report(translationSet: $submission->translation_set_identifier)->total());
    }

    public function testFiltersIncludePendingSubmissionsByTranslationLanguageAndCountry(): void
    {
        $pending = AnalyzeSourceRecords::history('draft_status_change', '2020-01-01', null, wiki: null, draft: StrTestHelper::generateUuid());
        $second = AnalyzeSourceRecords::history('draft_status_change', '2020-01-01', 'US');
        $second->update(['language' => 'en', 'translation_set_identifier' => StrTestHelper::generateUuid()]);
        self::assertSame('2', $this->report()->actorCount());
        self::assertSame('1', $this->report(country: '')->actorCount());
        self::assertSame('1', $this->report(country: 'US')->actorCount());
        self::assertSame('1', $this->report(wiki: $second->wiki_id)->total());
        self::assertSame('1', $this->report(translationSet: $pending->translation_set_identifier, language: 'ja')->total());
        self::assertSame('1', $this->report(translationSet: $second->translation_set_identifier, language: 'en')->actorCount());
        self::assertSame('0', $this->report(country: 'DE')->actorCount());
    }

    public function testMissingLegacyAttributionIsIncompleteButUnknownCountryAndAbsentIdentityAreValid(): void
    {
        AnalyzeSourceRecords::history('draft_status_change', '2020-01-01', null, wiki: null, draft: StrTestHelper::generateUuid());
        self::assertSame('1', $this->report(country: '')->actorCount());
        $legacy = AnalyzeSourceRecords::history('draft_status_change', '2020-01-02');
        $legacy->update(['translation_set_identifier' => null, 'language' => null]);
        $report = $this->report();
        self::assertNull($report->total());
        self::assertNull($report->actorCount());
        self::assertSame('incomplete', $report->days()[1]['availability']);
        self::assertSame(['history_attribution_missing'], $report->days()[1]['quality']);
        self::assertNull($this->report(language: 'ja')->total());
    }

    public function testEmptyHistoryIsMeasuredZeroWithoutABatch(): void
    {
        $report = $this->report();
        self::assertSame('0', $report->total());
        self::assertSame('0', $report->actorCount());
        self::assertSame(['measured', 'measured', 'measured'], array_column($report->days(), 'availability'));

    }

    public function testOnlyUnderReviewTransitionsAndPublicationsWithinTheUtcPeriodAreCounted(): void
    {
        AnalyzeSourceRecords::history('draft_status_change', '2019-12-31 23:59:59');
        AnalyzeSourceRecords::history('draft_status_change', '2020-01-01');
        $rejected = AnalyzeSourceRecords::history('draft_status_change', '2020-01-02');
        $rejected->update(['to_status' => 'draft']);
        AnalyzeSourceRecords::history('rollback', '2020-01-02');
        AnalyzeSourceRecords::history('publish', '2020-01-03 23:59:59');
        AnalyzeSourceRecords::history('publish', '2020-01-04');
        self::assertSame('1', $this->report()->total());
        self::assertSame('1', $this->report(Metric::PUBLICATIONS)->total());
    }

    public function testRevisionTracksMatchingHistoryAndReportsRemainIndependentOfPrincipalRecords(): void
    {
        $history = AnalyzeSourceRecords::history('draft_status_change', '2020-01-01');
        $revision = $this->report()->revision();
        self::assertNotNull($revision);
        self::assertSame($revision, $this->report()->revision());
        AnalyzeSourceRecords::history('publish', '2020-01-02');
        self::assertSame($revision, $this->report()->revision());
        AnalyzeSourceRecords::history('draft_status_change', '2020-01-02');
        self::assertNotSame($revision, $this->report()->revision());
        self::assertSame('2', $this->report()->actorCount());
        $history->delete();
        self::assertSame('1', $this->report()->total());
        self::assertSame('1', $this->report()->actorCount());
    }

    private function import(string $day): void
    {
        $http = new AnalyticsHttpStub();
        $http->empty = true;
        config(['app.frontend_url' => 'https://example.test']);
        $this->app()->instance(DailyMetricsFetchServiceInterface::class, new DailyMetricsFetchService($http->client(), new DailyWikiOperationsQueryService(), new RegionalMetricsFetchService($http->client(), new RegionNormalizer())));
        $usecase = $this->app()->make(ImportWikiDailySummary::class);
        DB::transaction(fn () => $usecase->process(new ImportWikiDailySummaryInput(new UtcDate($day), AnalyzeWikiMetrics::now()), new ImportWikiDailySummaryOutput()));
    }

    /** @phpstan-impure */
    private function report(Metric $metric = Metric::SUBMISSIONS, ?string $wiki = null, ?string $country = null, ?string $translationSet = null, ?string $language = null): MetricReportReadModel
    {
        $report = ($this->app()->make(GetWikiDailySummaryReport::class))->process(new GetWikiDailySummaryReportInput(
            $this->principalIdentifier,
            new ReportingPeriod(new UtcDate('2020-01-01'), new UtcDate('2020-01-03'), new DateTimeImmutable('2026-11-06T00:00:00Z')),
            wikiIdentifier: $wiki,
            translationSetIdentifier: $translationSet,
            language: $language,
            country: $country,
        ));

        return $metric === Metric::SUBMISSIONS ? $report->submissions() : $report->publications();
    }
}
