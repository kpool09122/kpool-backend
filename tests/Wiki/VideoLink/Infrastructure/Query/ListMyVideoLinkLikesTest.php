<?php

declare(strict_types=1);

namespace Tests\Wiki\VideoLink\Infrastructure\Query;

use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\VideoLink\Application\UseCase\Query\ListMyVideoLinkLikes\ListMyVideoLinkLikesInput;
use Source\Wiki\VideoLink\Application\UseCase\Query\ListMyVideoLinkLikes\ListMyVideoLinkLikesInterface;
use Source\Wiki\VideoLink\Application\UseCase\Query\ListMyVideoLinkLikes\ListMyVideoLinkLikesOutput;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoLinkIdentifier;
use Tests\Helper\CreateIdentity;
use Tests\Helper\CreatePrincipal;
use Tests\Helper\CreateVideoLink;
use Tests\Helper\CreateWiki;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class ListMyVideoLinkLikesTest extends TestCase
{
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
            'videoLinkIdentifier' => $ids['public'], 'url' => DB::table('wiki_video_links')->where('id', $ids['public'])->value('url'), 'title' => 'Test Video',
            'thumbnailUrl' => null, 'durationSeconds' => null, 'language' => 'en', 'resourceType' => 'agency', 'slug' => 'ag-public', 'likedAt' => '2026-02-01T00:00:00+00:00',
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
        self::assertCount(2, $queries);
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
