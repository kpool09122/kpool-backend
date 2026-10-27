<?php

declare(strict_types=1);

namespace Tests\Wiki\VideoLink\Infrastructure\Query;

use Application\Models\Wiki\VideoLink as VideoLinkModel;
use Application\Models\Wiki\VideoLinkLike as VideoLinkLikeModel;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\Support\TypedValue;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Playlist\Domain\Repository\PlaylistRepositoryInterface;
use Source\Wiki\Playlist\Domain\ValueObject\PlaylistIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\VideoLinkIdentifier;
use Source\Wiki\VideoLink\Application\UseCase\Query\ListMyVideoLinkLikes\ListMyVideoLinkLikesInput;
use Source\Wiki\VideoLink\Application\UseCase\Query\ListMyVideoLinkLikes\ListMyVideoLinkLikesInterface;
use Source\Wiki\VideoLink\Application\UseCase\Query\ListMyVideoLinkLikes\ListMyVideoLinkLikesOutput;
use Tests\Helper\CreateIdentity;
use Tests\Helper\CreatePrincipal;
use Tests\Helper\CreateVideoLink;
use Tests\Helper\CreateWiki;
use Tests\Helper\PlaylistTestHelper;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class ListMyVideoLinkLikesTest extends TestCase
{
    public function testEagerLoadsVideoAndWikiThroughTypedRelationships(): void
    {
        self::assertTrue((new VideoLinkLikeModel())->isRelation('videoLink'));
        self::assertTrue((new VideoLinkModel())->isRelation('wiki'));
        $principal = $this->createPrincipal();
        $wikiId = StrTestHelper::generateUuid();
        $videoId = StrTestHelper::generateUuid();
        CreateWiki::create($wikiId, 'talent', ['language' => 'ja', 'published_at' => '2026-01-01 00:00:00']);
        CreateVideoLink::create($videoId, ['wiki_id' => $wikiId, 'title' => 'Related video', 'duration_seconds' => 120]);
        DB::table('wiki_video_link_likes')->insert(['id' => StrTestHelper::generateUuid(), 'video_link_id' => $videoId, 'principal_id' => (string) $principal, 'created_at' => '2026-02-01 00:00:00']);
        $like = VideoLinkLikeModel::query()->with('videoLink.wiki')->where('video_link_id', $videoId)->firstOrFail();
        self::assertTrue($like->relationLoaded('videoLink'));
        self::assertNotNull($like->videoLink);
        self::assertTrue($like->videoLink->relationLoaded('wiki'));
        self::assertSame('Related video', $like->videoLink->title);
        self::assertSame(120, $like->videoLink->duration_seconds);
        self::assertNotNull($like->videoLink->wiki);
        self::assertSame($wikiId, $like->videoLink->wiki->id);
        self::assertSame('ja', $like->videoLink->wiki->language);
    }

    public function testFiltersPrivateMissingAndDeletedVideosAndOtherPrincipalsWithoutChangingBatchIds(): void
    {
        $principal = $this->createPrincipal();
        $otherPrincipal = $this->createPrincipal();
        $ids = [];
        foreach (['public', 'private', 'missing', 'deleted', 'other', 'unliked', 'older'] as $kind) {
            $wiki = StrTestHelper::generateUuid();
            $ids[$kind] = StrTestHelper::generateUuid();
            if ($kind !== 'missing') {
                CreateWiki::create($wiki, 'agency', ['language' => 'en', 'slug' => 'ag-' . $kind, 'published_at' => $kind === 'private' ? null : '2026-01-01 00:00:00']);
            }
            CreateVideoLink::create($ids[$kind], ['wiki_id' => $wiki, 'resource_type' => 'agency']);
            if ($kind !== 'unliked') {
                DB::table('wiki_video_link_likes')->insert(['id' => StrTestHelper::generateUuid(), 'video_link_id' => $ids[$kind], 'principal_id' => (string) ($kind === 'other' ? $otherPrincipal : $principal), 'created_at' => $kind === 'older' ? '2026-01-01 00:00:00' : '2026-02-01 00:00:00']);
            }
        }
        DB::table('wiki_video_links')->where('id', $ids['deleted'])->delete();
        $output = new ListMyVideoLinkLikesOutput();
        $query = $this->app()->make(ListMyVideoLinkLikesInterface::class);
        $query->process(new ListMyVideoLinkLikesInput($principal), $output);
        self::assertSame([$ids['public'], $ids['older']], $output->toArray()['likedVideoLinkIdentifiers']);
        self::assertSame(['currentPage' => 1, 'perPage' => 20, 'total' => 2], $output->toArray()['pagination']);
        self::assertSame([
            'provider' => 'youtube', 'externalVideoId' => substr(TypedValue::string(DB::table('wiki_video_links')->where('id', $ids['public'])->value('url')), -11), 'videoLinkIdentifier' => $ids['public'], 'url' => DB::table('wiki_video_links')->where('id', $ids['public'])->value('url'), 'title' => 'Test Video',
            'thumbnailUrl' => null, 'durationSeconds' => null, 'language' => 'en', 'resourceType' => 'agency', 'slug' => 'ag-public', 'likedAt' => '2026-02-01T00:00:00+00:00', 'playlists' => [],
        ], $output->toArray()['videos'][0]);
        $requested = array_map(static fn (string $id): VideoLinkIdentifier => new VideoLinkIdentifier($id), array_values($ids));
        $query->process(new ListMyVideoLinkLikesInput($principal, $requested, 99, 1), $output);
        $expected = [$ids['public'], $ids['private'], $ids['missing'], $ids['older']];
        sort($expected);
        self::assertSame($expected, $output->toArray()['likedVideoLinkIdentifiers']);
        $publicIds = [$ids['public'], $ids['older']];
        sort($publicIds);
        self::assertSame($publicIds, array_column($output->toArray()['videos'], 'videoLinkIdentifier'));
        self::assertNull($output->toArray()['pagination']);
        $query->process(new ListMyVideoLinkLikesInput($principal, [new VideoLinkIdentifier($ids['public'])]), $output);
        self::assertSame([$ids['public']], $output->toArray()['likedVideoLinkIdentifiers']);
        $query->process(new ListMyVideoLinkLikesInput($this->createPrincipal()), $output);
        self::assertSame(['likedVideoLinkIdentifiers' => [], 'videos' => [], 'pagination' => ['currentPage' => 1, 'perPage' => 20, 'total' => 0]], $output->toArray());
    }

    public function testHundredRequestedIdsAreNotPaginatedAndListingUsesTwentyByDefault(): void
    {
        $principal = $this->createPrincipal();
        $wiki = StrTestHelper::generateUuid();
        CreateWiki::create($wiki, 'talent', ['published_at' => '2026-01-01 00:00:00']);
        $ids = [];
        for ($i = 0; $i < 100; ++$i) {
            $id = StrTestHelper::generateUuid();
            $ids[] = $id;
            CreateVideoLink::create($id, ['wiki_id' => $wiki]);
            DB::table('wiki_video_link_likes')->insert(['id' => StrTestHelper::generateUuid(), 'video_link_id' => $id, 'principal_id' => (string) $principal, 'created_at' => '2026-01-01 00:00:00']);
        }
        sort($ids);
        $output = new ListMyVideoLinkLikesOutput();
        $query = $this->app()->make(ListMyVideoLinkLikesInterface::class);
        $query->process(new ListMyVideoLinkLikesInput($principal, array_map(static fn (string $id): VideoLinkIdentifier => new VideoLinkIdentifier($id), array_reverse($ids)), 2, 1), $output);
        self::assertSame($ids, $output->toArray()['likedVideoLinkIdentifiers']);
        self::assertSame($ids, array_column($output->toArray()['videos'], 'videoLinkIdentifier'));
        self::assertNull($output->toArray()['pagination']);
        $query->process(new ListMyVideoLinkLikesInput($principal), $output);
        self::assertSame(array_slice($ids, 0, 20), $output->toArray()['likedVideoLinkIdentifiers']);
        self::assertCount(20, $output->toArray()['videos']);
        self::assertSame(['currentPage' => 1, 'perPage' => 20, 'total' => 100], $output->toArray()['pagination']);
    }

    public function testIncludesOnlyOwnedPlaylistMembershipsAcrossLanguagesAndDeletedSourcesWithoutNPlusOne(): void
    {
        $principal = $this->createPrincipal();
        $other = $this->createPrincipal();
        $ids = [];
        foreach (['ja', 'ko', 'en'] as $language) {
            $wiki = StrTestHelper::generateUuid();
            $video = StrTestHelper::generateUuid();
            $ids[] = $video;
            CreateWiki::create($wiki, 'group', ['language' => $language, 'published_at' => '2026-01-01 00:00:00']);
            CreateVideoLink::create($video, ['wiki_id' => $wiki, 'url' => $language === 'en' ? 'https://youtu.be/abcdefghijk' : 'https://www.youtube.com/watch?v=abcdefghijk']);
            DB::table('wiki_video_link_likes')->insert(['id' => StrTestHelper::generateUuid(), 'video_link_id' => $video, 'principal_id' => (string) $principal, 'created_at' => '2026-02-01 00:00:00']);
        }
        $repository = $this->app()->make(PlaylistRepositoryInterface::class);
        $first = new PlaylistIdentifier('019a0000-0000-7000-8000-000000000001');
        $second = new PlaylistIdentifier('019a0000-0000-7000-8000-000000000002');
        foreach ([$second, $first] as $playlistId) {
            $repository->save(PlaylistTestHelper::playlist($playlistId, $principal, [PlaylistTestHelper::item($playlistId, 0, externalId: 'abcdefghijk')]));
        }
        $unrelated = new PlaylistIdentifier(StrTestHelper::generateUuid());
        $repository->save(PlaylistTestHelper::playlist($unrelated, $principal, [PlaylistTestHelper::item($unrelated, 0, externalId: 'lmnopqrstuv')]));
        $foreign = new PlaylistIdentifier(StrTestHelper::generateUuid());
        $repository->save(PlaylistTestHelper::playlist($foreign, $other, [PlaylistTestHelper::item($foreign, 0, externalId: 'abcdefghijk')]));
        $output = new ListMyVideoLinkLikesOutput();
        $query = $this->app()->make(ListMyVideoLinkLikesInterface::class);
        DB::enableQueryLog();
        DB::flushQueryLog();
        $query->process(new ListMyVideoLinkLikesInput($principal), $output);
        $playlistQueries = array_filter(DB::getQueryLog(), static fn (array $query): bool => str_contains($query['query'], 'wiki_playlist'));
        DB::disableQueryLog();
        self::assertCount(2, $playlistQueries);
        self::assertCount(3, $output->toArray()['videos']);
        foreach ($output->toArray()['videos'] as $video) {
            self::assertSame([
                ['playlistIdentifier' => (string) $first, 'name' => 'My playlist'],
                ['playlistIdentifier' => (string) $second, 'name' => 'My playlist'],
            ], $video['playlists']);
        }
        $repository->deleteByIdAndPrincipal($first, $principal);
        $query->process(new ListMyVideoLinkLikesInput($principal, [new VideoLinkIdentifier($ids[0])]), $output);
        self::assertSame([['playlistIdentifier' => (string) $second, 'name' => 'My playlist']], $output->toArray()['videos'][0]['playlists']);
    }

    private function createPrincipal(): PrincipalIdentifier
    {
        $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
        $principal = new PrincipalIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identity, ['email' => (string) $identity . '@example.com']);
        CreatePrincipal::create($principal, $identity);

        return $principal;
    }

    public function testReturnsOnlyRequestedIdsLikedByAuthenticatedPrincipalInStableOrder(): void
    {
        $principals = [];
        foreach ([0, 1] as $i) {
            $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
            $principals[$i] = new PrincipalIdentifier(StrTestHelper::generateUuid());
            CreateIdentity::create($identity, ['email' => (string) $identity . '@example.com']);
            CreatePrincipal::create($principals[$i], $identity);
        }
        $ids = array_map(static fn (): VideoLinkIdentifier => new VideoLinkIdentifier(StrTestHelper::generateUuid()), range(1, 5));
        foreach ($ids as $id) {
            CreateVideoLink::create((string) $id);
        }
        foreach ([[$ids[0], $principals[0]], [$ids[1], $principals[0]], [$ids[2], $principals[0]], [$ids[3], $principals[1]]] as [$video, $principal]) {
            DB::table('wiki_video_link_likes')->insert(['id' => StrTestHelper::generateUuid(), 'video_link_id' => (string) $video, 'principal_id' => (string) $principal, 'created_at' => now()]);
        }
        $output = new ListMyVideoLinkLikesOutput();
        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->app()->make(ListMyVideoLinkLikesInterface::class)->process(new ListMyVideoLinkLikesInput($principals[0], [$ids[1], $ids[0], $ids[1], $ids[3], $ids[4], new VideoLinkIdentifier(StrTestHelper::generateUuid())]), $output);
        $queries = DB::getQueryLog();
        DB::disableQueryLog();
        $expected = [(string) $ids[0], (string) $ids[1]];
        sort($expected);
        self::assertSame(['likedVideoLinkIdentifiers' => $expected, 'videos' => [], 'pagination' => null], $output->toArray());
        self::assertCount(2, $queries);
        $this->app()->make(ListMyVideoLinkLikesInterface::class)->process(new ListMyVideoLinkLikesInput($principals[1], [$ids[0], $ids[4]]), $output);
        self::assertSame(['likedVideoLinkIdentifiers' => [], 'videos' => [], 'pagination' => null], $output->toArray());
    }

    public function testListsOwnPublishedVideosAcrossLanguagesAndTypesWithStablePagination(): void
    {
        $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
        $principal = new PrincipalIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identity);
        CreatePrincipal::create($principal, $identity);
        $ids = [];
        foreach ([['ja', 'talent'], ['ko', 'group'], ['en', 'song']] as [$language, $type]) {
            $wiki = StrTestHelper::generateUuid();
            $id = StrTestHelper::generateUuid();
            $ids[] = $id;
            CreateWiki::create($wiki, $type, ['language' => $language, 'published_at' => '2026-01-01 00:00:00']);
            CreateVideoLink::create($id, ['wiki_id' => $wiki, 'resource_type' => $type, 'url' => 'https://www.youtube.com/watch?v=abcdefghijk', 'title' => 'Shared video', 'thumbnail_url' => 'https://img.youtube.com/vi/abcdefghijk/default.jpg', 'duration_seconds' => 120]);
            DB::table('wiki_video_link_likes')->insert(['id' => StrTestHelper::generateUuid(), 'video_link_id' => $id, 'principal_id' => (string) $principal, 'created_at' => '2026-02-01 12:00:00']);
        }
        sort($ids);
        $output = new ListMyVideoLinkLikesOutput();
        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->app()->make(ListMyVideoLinkLikesInterface::class)->process(new ListMyVideoLinkLikesInput($principal, null, 1, 2), $output);
        $queries = DB::getQueryLog();
        DB::disableQueryLog();
        $data = $output->toArray();
        self::assertSame(array_slice($ids, 0, 2), $data['likedVideoLinkIdentifiers']);
        self::assertSame(['currentPage' => 1, 'perPage' => 2, 'total' => 3], $data['pagination']);
        self::assertCount(2, $data['videos']);
        self::assertSame($data['likedVideoLinkIdentifiers'], array_column($data['videos'], 'videoLinkIdentifier'));
        self::assertCount(5, $queries);
        foreach ($data['videos'] as $video) {
            self::assertSame('https://www.youtube.com/watch?v=abcdefghijk', $video['url']);
            self::assertSame('Shared video', $video['title']);
            self::assertSame(120, $video['durationSeconds']);
            self::assertSame('https://img.youtube.com/vi/abcdefghijk/default.jpg', $video['thumbnailUrl']);
            self::assertSame('2026-02-01T12:00:00+00:00', $video['likedAt']);
            self::assertContains($video['language'], ['ja', 'ko', 'en']);
            self::assertContains($video['resourceType'], ['talent', 'group', 'song']);
            self::assertNotEmpty($video['slug']);
        }
        $this->app()->make(ListMyVideoLinkLikesInterface::class)->process(new ListMyVideoLinkLikesInput($principal, null, 2, 2), $output);
        self::assertSame([$ids[2]], $output->toArray()['likedVideoLinkIdentifiers']);
        self::assertSame(['currentPage' => 2, 'perPage' => 2, 'total' => 3], $output->toArray()['pagination']);
        $this->app()->make(ListMyVideoLinkLikesInterface::class)->process(new ListMyVideoLinkLikesInput($principal, null, 3, 2), $output);
        self::assertSame([], $output->toArray()['videos']);
        self::assertSame([], $output->toArray()['likedVideoLinkIdentifiers']);
        self::assertSame(['currentPage' => 3, 'perPage' => 2, 'total' => 3], $output->toArray()['pagination']);
    }
}
