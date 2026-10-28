<?php

declare(strict_types=1);

namespace Tests\Wiki\VideoLink\Infrastructure\Query;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Shared\Domain\ValueObject\VideoLinkIdentifier;
use Source\Wiki\VideoLink\Application\UseCase\Query\ListVideos\ListVideosInput;
use Source\Wiki\VideoLink\Application\UseCase\Query\ListVideos\ListVideosInterface;
use Source\Wiki\VideoLink\Application\UseCase\Query\ListVideos\ListVideosOutput;
use Source\Wiki\VideoLink\Domain\Repository\VideoLinkRepositoryInterface;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Tests\Helper\CreateIdentity;
use Tests\Helper\CreatePrincipal;
use Tests\Helper\CreateVideoLink;
use Tests\Helper\CreateWiki;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class ListVideosTest extends TestCase
{
    public function testRecentRequestsAreDistinctCountedBeforeLikesAndExpireAtExactlySevenDays(): void
    {
        CarbonImmutable::setTestNow('2026-10-28 00:00:00 UTC');

        try {
            $wiki = StrTestHelper::generateUuid();
            $other = StrTestHelper::generateUuid();
            CreateWiki::create($wiki, 'talent', ['slug' => 'tl-watch', 'language' => 'ja', 'published_at' => '2026-01-01']);
            CreateWiki::create($other, 'talent', ['slug' => 'tl-other', 'language' => 'ja', 'published_at' => '2026-01-01']);
            $ids = [StrTestHelper::generateUuid(), StrTestHelper::generateUuid(), StrTestHelper::generateUuid(), StrTestHelper::generateUuid()];
            sort($ids);
            foreach ($ids as $i => $id) {
                CreateVideoLink::create($id, ['wiki_id' => $wiki, 'duration_seconds' => $i === 3 ? 31 : 30]);
                for ($j = 0; $j < ($i === 0 ? 1 : 2); ++$j) {
                    DB::table('wiki_video_watch_requests')->insert(['id' => StrTestHelper::generateUuid(), 'video_link_id' => $id, 'wiki_id' => $wiki, 'principal_id' => StrTestHelper::generateUuid(), 'sent_at' => $i === 2 ? '2026-10-21 00:00:00' : '2026-10-21 00:00:01']);
                }
            }
            // A third, expired request must not put the one-request video ahead of two-request videos.
            DB::table('wiki_video_watch_requests')->insert(['id' => StrTestHelper::generateUuid(), 'video_link_id' => $ids[0], 'wiki_id' => $wiki, 'principal_id' => StrTestHelper::generateUuid(), 'sent_at' => '2026-10-20 23:59:59']);
            foreach ([0, 1, 2, 3] as $i) {
                $principal = new PrincipalIdentifier(StrTestHelper::generateUuid());
                $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
                CreateIdentity::create($identity, ['email' => (string) $identity . '@example.com']);
                CreatePrincipal::create($principal, $identity);
                DB::table('wiki_video_link_likes')->insert(['id' => StrTestHelper::generateUuid(), 'video_link_id' => $ids[0], 'principal_id' => (string) $principal, 'created_at' => now()]);
            }
            $output = new ListVideosOutput();
            $query = $this->app()->make(ListVideosInterface::class);
            $query->process(new ListVideosInput(new Slug('tl-watch'), Language::JAPANESE, ResourceType::TALENT, recentWatchRequestsOnly: true, sortByWatchRequests: true), $output);
            self::assertSame([$ids[1], $ids[3], $ids[0]], array_column($output->toArray()['videos'], 'videoLinkIdentifier'));
            $query->process(new ListVideosInput(new Slug('tl-watch'), Language::JAPANESE, ResourceType::TALENT, maxDurationSeconds: 30, recentWatchRequestsOnly: true, sortByWatchRequests: true), $output);
            self::assertSame([$ids[1], $ids[0]], array_column($output->toArray()['videos'], 'videoLinkIdentifier'));
            $query->process(new ListVideosInput(new Slug('tl-watch'), Language::JAPANESE, ResourceType::TALENT, sortByWatchRequests: true), $output);
            self::assertSame([$ids[1], $ids[3], $ids[0], $ids[2]], array_column($output->toArray()['videos'], 'videoLinkIdentifier'));
            // Equal request counts use like count before the stable identifier tie-breaker.
            DB::table('wiki_video_link_likes')->insert(['id' => StrTestHelper::generateUuid(), 'video_link_id' => $ids[3], 'principal_id' => (string) $principal, 'created_at' => now()]);
            DB::enableQueryLog();
            DB::flushQueryLog();
            $query->process(new ListVideosInput(new Slug('tl-watch'), Language::JAPANESE, ResourceType::TALENT, minDurationSeconds: 30, maxDurationSeconds: 31, recentWatchRequestsOnly: true, sortByWatchRequests: true), $output);
            $queries = DB::getQueryLog();
            DB::disableQueryLog();
            self::assertCount(2, $queries);
            self::assertSame([$ids[3], $ids[1], $ids[0]], array_column($output->toArray()['videos'], 'videoLinkIdentifier'));
            foreach ($output->toArray()['videos'] as $row) {
                self::assertArrayNotHasKey('sent', $row);
                self::assertArrayNotHasKey('principalIdentifier', $row);
                self::assertArrayNotHasKey('nextSendAt', $row);
            }
            CarbonImmutable::setTestNow('2026-10-28 00:00:01 UTC');
            $query->process(new ListVideosInput(new Slug('tl-watch'), Language::JAPANESE, ResourceType::TALENT, recentWatchRequestsOnly: true), $output);
            self::assertSame([], $output->toArray()['videos']);
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function testReadsStoredTikTokIdentityCoverAndDurationWithoutNetwork(): void
    {
        Http::preventStrayRequests();
        $wiki = StrTestHelper::generateUuid();
        $video = StrTestHelper::generateUuid();
        CreateWiki::create($wiki, 'group', ['slug' => 'gr-tiktok', 'language' => 'ja', 'published_at' => '2026-01-01']);
        CreateVideoLink::create($video, ['wiki_id' => $wiki, 'resource_type' => 'group', 'url' => 'https://www.tiktok.com/@scout2015/video/6718335390845095173', 'duration_seconds' => 12]);
        DB::table('wiki_video_links')->where('id', $video)->update(['thumbnail_path' => 'images/cover.webp']);
        $output = new ListVideosOutput();
        $this->app()->make(ListVideosInterface::class)->process(new ListVideosInput(new Slug('gr-tiktok'), Language::JAPANESE, ResourceType::GROUP, 12, 12), $output);
        $row = $output->toArray()['videos'][0];
        self::assertSame('tiktok', $row['provider']);
        self::assertSame('6718335390845095173', $row['externalVideoId']);
        self::assertSame(12, $row['durationSeconds']);
        self::assertStringContainsString('images/cover.webp', $row['thumbnailUrl'] ?? '');
        $this->app()->make(ListVideosInterface::class)->process(new ListVideosInput(new Slug('gr-tiktok'), Language::JAPANESE, ResourceType::GROUP, 13), $output);
        self::assertSame([], $output->toArray()['videos']);
        Http::assertNothingSent();
    }

    public function testReturnsManualAndAutomaticVideosForRequestedPublishedWikiInStableOrder(): void
    {
        $id = StrTestHelper::generateUuid();
        $other = StrTestHelper::generateUuid();
        CreateWiki::create($id, 'group', ['slug' => 'gr-test', 'language' => 'ja', 'published_at' => '2026-01-01']);
        CreateWiki::create($other, 'group', ['slug' => 'gr-test', 'language' => 'en', 'published_at' => '2026-01-01']);
        $ids = [StrTestHelper::generateUuid(), StrTestHelper::generateUuid(), StrTestHelper::generateUuid()];
        sort($ids);
        foreach (['youtube_auto_view_count', 'youtube_auto_like_count', 'youtube_auto_recent_popular'] as $i => $usage) {
            CreateVideoLink::create($ids[$i], ['wiki_id' => $id, 'resource_type' => 'group', 'video_usage' => $usage, 'created_at' => '2026-10-01 00:00:00']);
        }
        $manualId = StrTestHelper::generateUuid();
        $creator = StrTestHelper::generateUuid();
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identityIdentifier, ['identity_name' => '動画の作成者']);
        CreatePrincipal::create(new PrincipalIdentifier($creator), $identityIdentifier);
        CreateVideoLink::create($manualId, ['wiki_id' => $id, 'resource_type' => 'group', 'video_usage' => 'music_video', 'duration_seconds' => 253, 'creator_principal_id' => $creator, 'created_at' => '2026-10-02 00:00:00']);
        CreateVideoLink::create(StrTestHelper::generateUuid(), ['wiki_id' => $other, 'resource_type' => 'group', 'video_usage' => 'youtube_auto_view_count']);
        CreateVideoLink::create(StrTestHelper::generateUuid(), ['wiki_id' => $id, 'resource_type' => 'talent', 'video_usage' => 'youtube_auto_view_count']);
        $payload = $this->process('gr-test', Language::JAPANESE, ResourceType::GROUP);
        $this->assertSame([$manualId, ...$ids], array_column($payload['videos'], 'videoLinkIdentifier'));
        $this->assertSame(['provider', 'externalVideoId', 'videoLinkIdentifier', 'url', 'title', 'thumbnailUrl', 'publishedAt', 'videoUsage', 'durationSeconds', 'creatorPrincipalIdentifier', 'creatorIdentityName', 'likeCount'], array_keys($payload['videos'][0]));
        $this->assertNull($payload['videos'][0]['thumbnailUrl']);
        $this->assertNull($payload['videos'][0]['publishedAt']);
        $this->assertSame('Test Video', $payload['videos'][0]['title']);
        $this->assertSame(0, $payload['videos'][0]['likeCount']);
        $this->assertSame(253, $payload['videos'][0]['durationSeconds']);
        $this->assertNull($payload['videos'][1]['durationSeconds']);
        $this->assertSame($creator, $payload['videos'][0]['creatorPrincipalIdentifier']);
        $this->assertSame('動画の作成者', $payload['videos'][0]['creatorIdentityName']);
        $this->assertNull($payload['videos'][1]['creatorPrincipalIdentifier']);
        $this->assertNull($payload['videos'][1]['creatorIdentityName']);
    }

    public function testCountsLikesWithoutPerVideoQueriesAndKeepsPersonalStatePrivate(): void
    {
        $wiki = StrTestHelper::generateUuid();
        CreateWiki::create($wiki, 'group', ['slug' => 'gr-likes', 'language' => 'ja', 'published_at' => '2026-01-01']);
        $ids = [StrTestHelper::generateUuid(), StrTestHelper::generateUuid(), StrTestHelper::generateUuid()];
        sort($ids);
        foreach ($ids as $i => $id) {
            CreateVideoLink::create($id, ['wiki_id' => $wiki, 'resource_type' => 'group', 'video_usage' => $i === 0 ? 'music_video' : 'youtube_auto_view_count', 'created_at' => '2026-10-01']);
        }
        foreach ([0, 1] as $i) {
            $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
            $principal = new PrincipalIdentifier(StrTestHelper::generateUuid());
            CreateIdentity::create($identity, ['email' => (string) $identity . '@example.com']);
            CreatePrincipal::create($principal, $identity);
            foreach (array_slice($ids, 0, $i + 1) as $id) {
                DB::table('wiki_video_link_likes')->insert(['id' => StrTestHelper::generateUuid(), 'video_link_id' => $id, 'principal_id' => (string) $principal, 'created_at' => now()]);
            }
        }
        DB::enableQueryLog();
        DB::flushQueryLog();
        $payload = $this->process('gr-likes', Language::JAPANESE, ResourceType::GROUP);
        $queries = DB::getQueryLog();
        DB::disableQueryLog();
        self::assertSame([2, 1, 0], array_column($payload['videos'], 'likeCount'));
        self::assertCount(2, $queries);
        self::assertStringContainsString('count(*)', $queries[1]['query']);
        foreach ($payload['videos'] as $video) {
            self::assertArrayNotHasKey('principal_id', $video);
            self::assertArrayNotHasKey('liked', $video);
        }
    }

    /** @param list<?int> $expectedDurations */
    #[DataProvider('durationRanges')]
    public function testFiltersDurationWithInclusiveBounds(?int $minimum, ?int $maximum, array $expectedDurations): void
    {
        $wikiId = StrTestHelper::generateUuid();
        CreateWiki::create($wikiId, 'group', ['slug' => 'gr-duration', 'language' => 'ja', 'published_at' => '2026-01-01']);
        foreach ([null, 0, 599, 600, 1200, 2400, 3600] as $duration) {
            CreateVideoLink::create(StrTestHelper::generateUuid(), ['wiki_id' => $wikiId, 'resource_type' => 'group', 'duration_seconds' => $duration]);
        }
        $output = new ListVideosOutput();
        $input = new ListVideosInput(new Slug('gr-duration'), Language::JAPANESE, ResourceType::GROUP, $minimum, $maximum);
        $this->app()->make(ListVideosInterface::class)->process($input, $output);
        $durations = array_column($output->toArray()['videos'], 'durationSeconds');
        $this->assertEqualsCanonicalizing($expectedDurations, $durations);
    }

    /** @return list<array{?int, ?int, list<?int>}> */
    public static function durationRanges(): array
    {
        return [
            [null, null, [null, 0, 599, 600, 1200, 2400, 3600]],
            [600, null, [600, 1200, 2400, 3600]],
            [null, 600, [0, 599, 600]],
            [600, 1200, [600, 1200]],
            [2400, null, [2400, 3600]],
            [600, 600, [600]],
            [0, null, [0, 599, 600, 1200, 2400, 3600]],
            [null, 0, [0]],
            [1, 1, []],
        ];
    }

    public function testReturnsManuallyCreatedAgencyVideos(): void
    {
        $id = StrTestHelper::generateUuid();
        CreateWiki::create($id, 'agency', ['slug' => 'ag-test','published_at' => '2026-01-01']);
        $videoId = StrTestHelper::generateUuid();
        CreateVideoLink::create($videoId, ['wiki_id' => $id, 'resource_type' => 'agency', 'video_usage' => 'music_video']);
        $payload = $this->process('ag-test', Language::KOREAN, ResourceType::AGENCY);
        $this->assertSame([$videoId], array_column($payload['videos'], 'videoLinkIdentifier'));
        $this->assertSame('music_video', $payload['videos'][0]['videoUsage']);
    }

    public function testDeletionRemovesOnlyTargetFromPublicList(): void
    {
        $wiki = StrTestHelper::generateUuid();
        CreateWiki::create($wiki, 'group', ['slug' => 'gr-delete', 'language' => 'ja', 'published_at' => '2026-01-01']);
        $target = StrTestHelper::generateUuid();
        $retained = StrTestHelper::generateUuid();
        CreateVideoLink::create($target, ['wiki_id' => $wiki, 'resource_type' => 'group']);
        CreateVideoLink::create($retained, ['wiki_id' => $wiki, 'resource_type' => 'group']);
        $this->assertCount(2, $this->process('gr-delete', Language::JAPANESE, ResourceType::GROUP)['videos']);
        $this->app()->make(VideoLinkRepositoryInterface::class)->delete(new VideoLinkIdentifier($target));
        $payload = $this->process('gr-delete', Language::JAPANESE, ResourceType::GROUP);
        $this->assertSame([$retained], array_column($payload['videos'], 'videoLinkIdentifier'));
        $this->assertDatabaseMissing('wiki_video_links', ['id' => $target]);
        $this->assertDatabaseHas('wiki_video_links', ['id' => $retained]);
    }

    public function testAgencyManualVideoReturnsCreator(): void
    {
        $id = StrTestHelper::generateUuid();
        $creator = StrTestHelper::generateUuid();
        CreateWiki::create($id, 'agency', ['slug' => 'ag-manual', 'published_at' => '2026-01-01']);
        CreateVideoLink::create(StrTestHelper::generateUuid(), ['wiki_id' => $id, 'resource_type' => 'agency', 'creator_principal_id' => $creator]);
        $videos = $this->process('ag-manual', Language::KOREAN, ResourceType::AGENCY)['videos'];
        $this->assertCount(1, $videos);
        $this->assertSame($creator, $videos[0]['creatorPrincipalIdentifier']);
        $this->assertNull($videos[0]['creatorIdentityName']);
    }

    public function testDeletedIdentityReturnsNullCreatorName(): void
    {
        $wiki = StrTestHelper::generateUuid();
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identityIdentifier);
        CreatePrincipal::create($principalIdentifier, $identityIdentifier);
        CreateWiki::create($wiki, 'group', ['slug' => 'gr-missing-identity', 'language' => 'ja', 'published_at' => '2026-01-01']);
        CreateVideoLink::create(StrTestHelper::generateUuid(), ['wiki_id' => $wiki, 'resource_type' => 'group', 'creator_principal_id' => (string) $principalIdentifier]);
        DB::table('identities')->where('id', (string) $identityIdentifier)->delete();
        $videos = $this->process('gr-missing-identity', Language::JAPANESE, ResourceType::GROUP)['videos'];
        $this->assertSame((string) $principalIdentifier, $videos[0]['creatorPrincipalIdentifier']);
        $this->assertNull($videos[0]['creatorIdentityName']);
    }

    public function testAutoVideoDoesNotReturnCreatorEvenWhenStored(): void
    {
        $wiki = StrTestHelper::generateUuid();
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identityIdentifier, ['identity_name' => '作成者']);
        CreatePrincipal::create($principalIdentifier, $identityIdentifier);
        CreateWiki::create($wiki, 'group', ['slug' => 'gr-auto-creator', 'language' => 'ja', 'published_at' => '2026-01-01']);
        CreateVideoLink::create(StrTestHelper::generateUuid(), ['wiki_id' => $wiki, 'resource_type' => 'group', 'video_usage' => 'youtube_auto_view_count', 'creator_principal_id' => (string) $principalIdentifier]);
        $videos = $this->process('gr-auto-creator', Language::JAPANESE, ResourceType::GROUP)['videos'];
        $this->assertNull($videos[0]['creatorPrincipalIdentifier']);
        $this->assertNull($videos[0]['creatorIdentityName']);
    }

    #[DataProvider('resourceTypes')]
    public function testResolvesAllPublishedResourceTypes(string $type, string $slug): void
    {
        CreateWiki::create(StrTestHelper::generateUuid(), $type, ['slug' => $slug, 'published_at' => '2026-01-01']);
        $this->assertSame(['videos' => []], $this->process($slug, Language::KOREAN, ResourceType::from($type)));
    }

    /** @return list<array{string,string}> */
    public static function resourceTypes(): array
    {
        return [['agency', 'ag-test'], ['group', 'gr-test'], ['song', 'sg-test'], ['talent', 'tl-test']];
    }

    #[DataProvider('missingWikis')]
    public function testRejectsMissingUnpublishedOrDifferentScope(string $slug, Language $language, ResourceType $type): void
    {
        CreateWiki::create(StrTestHelper::generateUuid(), 'group', ['slug' => 'gr-test', 'language' => 'ja', 'published_at' => '2026-01-01']);
        CreateWiki::create(StrTestHelper::generateUuid(), 'group', ['slug' => 'gr-unpublished', 'language' => 'ja']);
        $this->expectException(WikiNotFoundException::class);
        $this->process($slug, $language, $type);
    }

    /** @return list<array{string,Language,ResourceType}> */
    public static function missingWikis(): array
    {
        return [['gr-missing', Language::JAPANESE, ResourceType::GROUP], ['gr-test', Language::ENGLISH, ResourceType::GROUP], ['gr-test', Language::JAPANESE, ResourceType::TALENT], ['gr-unpublished', Language::JAPANESE, ResourceType::GROUP]];
    }

    /** @return array{videos: list<array<string, mixed>>} */
    private function process(string $slug, Language $language, ResourceType $type): array
    {
        $output = new ListVideosOutput();
        $this->app()->make(ListVideosInterface::class)->process(new ListVideosInput(new Slug($slug), $language, $type), $output);

        return $output->toArray();
    }
}
