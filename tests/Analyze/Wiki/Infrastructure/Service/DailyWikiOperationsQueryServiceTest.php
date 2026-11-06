<?php

declare(strict_types=1);

namespace Tests\Analyze\Wiki\Infrastructure\Service;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Source\Analyze\Wiki\Infrastructure\Service\DailyWikiOperationsQueryService;
use Tests\Helper\AnalyzeSourceRecords;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class DailyWikiOperationsQueryServiceTest extends TestCase
{
    public function testCountsActionsUsingEventTimeAndCountryAndIgnoresOtherActions(): void
    {
        AnalyzeSourceRecords::history('draft_status_change', '2020-01-01');
        AnalyzeSourceRecords::history('draft_status_change', '2020-01-01 12:00:00');
        AnalyzeSourceRecords::history('publish', '2020-01-01 23:59:59', null);
        AnalyzeSourceRecords::history('rollback', '2020-01-01');
        AnalyzeSourceRecords::history('publish', '2020-01-02');
        $counts = (new DailyWikiOperationsQueryService())->find(new DateTimeImmutable('2020-01-01T00:00:00Z'), new DateTimeImmutable('2020-01-02T00:00:00Z'));
        self::assertCount(2, $counts);
        $jp = array_values(array_filter($counts, static fn ($count): bool => $count->country() === 'JP'))[0];
        $unknown = array_values(array_filter($counts, static fn ($count): bool => $count->country() === ''))[0];
        self::assertSame('2', $jp->submissions());
        self::assertSame('0', $jp->publications());
        self::assertSame('1', $unknown->publications());
    }

    public function testDraftSubmissionCanUseStableWikiIdentityFromLaterPublication(): void
    {
        $draft = StrTestHelper::generateUuid();
        AnalyzeSourceRecords::history('draft_status_change', '2020-01-01', wiki: null, draft: $draft);
        $published = AnalyzeSourceRecords::history('publish', '2020-01-03', draft: $draft);
        $counts = (new DailyWikiOperationsQueryService())->find(new DateTimeImmutable('2020-01-01T00:00:00Z'), new DateTimeImmutable('2020-01-02T00:00:00Z'));
        self::assertSame($published->wiki_id, $counts[0]->wikiIdentifier());
        self::assertSame('1', $counts[0]->submissions());
    }

    public function testMultipleDraftsAndRepeatedSubmissionsUseThreeBoundedQueries(): void
    {
        $expectedWikiIdentifiers = [];
        for ($index = 0; $index < 5; ++$index) {
            $draft = StrTestHelper::generateUuid();
            AnalyzeSourceRecords::history('draft_status_change', '2020-01-01', wiki: null, draft: $draft);
            AnalyzeSourceRecords::history('draft_status_change', '2020-01-01 12:00:00', wiki: null, draft: $draft);
            $publication = AnalyzeSourceRecords::history('publish', '2020-01-03', wiki: StrTestHelper::generateUuid(), draft: $draft);
            $expectedWikiIdentifiers[] = $publication->wiki_id;
            AnalyzeSourceRecords::history('publish', '2020-01-04', wiki: StrTestHelper::generateUuid(), draft: $draft);
        }
        DB::flushQueryLog();
        DB::enableQueryLog();

        try {
            $counts = (new DailyWikiOperationsQueryService())->find(new DateTimeImmutable('2020-01-01T00:00:00Z'), new DateTimeImmutable('2020-01-02T00:00:00Z'));
            self::assertCount(3, DB::getQueryLog());
        } finally {
            DB::disableQueryLog();
            DB::flushQueryLog();
        }
        self::assertCount(5, $counts);
        self::assertEqualsCanonicalizing($expectedWikiIdentifiers, array_map(static fn ($count): ?string => $count->wikiIdentifier(), $counts));
        foreach ($counts as $count) {
            self::assertSame('2', $count->submissions());
            self::assertSame('0', $count->publications());
        }
    }

    public function testUnresolvedDraftDoesNotDisappearAsZero(): void
    {
        AnalyzeSourceRecords::history('draft_status_change', '2020-01-01', wiki: null, draft: StrTestHelper::generateUuid());
        $counts = (new DailyWikiOperationsQueryService())->find(new DateTimeImmutable('2020-01-01T00:00:00Z'), new DateTimeImmutable('2020-01-02T00:00:00Z'));
        self::assertSame('1', $counts[0]->submissions());
        self::assertNull($counts[0]->wikiIdentifier());
    }
}
