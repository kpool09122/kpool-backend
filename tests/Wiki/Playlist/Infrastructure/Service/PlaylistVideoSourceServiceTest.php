<?php

declare(strict_types=1);

namespace Tests\Wiki\Playlist\Infrastructure\Service;

use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Playlist\Application\Service\PlaylistVideoSourceServiceInterface;
use Source\Wiki\Playlist\Domain\ValueObject\PlaylistIdentifier;
use Source\Wiki\Playlist\Domain\ValueObject\VideoProvider;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\VideoLinkIdentifier;
use Source\Wiki\VideoLink\Application\Exception\VideoLinkNotFoundException;
use Tests\Helper\CreateIdentity;
use Tests\Helper\CreatePrincipal;
use Tests\Helper\CreateVideoLink;
use Tests\Helper\CreateWiki;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class PlaylistVideoSourceServiceTest extends TestCase
{
    public function testResolvesCanonicalVideoWithNullableSnapshotMetadata(): void
    {
        [$owner, $video, $playlist] = $this->fixture();
        $items = $this->app()->make(PlaylistVideoSourceServiceInterface::class)->resolveMany([$video], $owner, $playlist);
        $item = $items[(string) $video];
        self::assertSame($playlist, $item->playlistIdentifier());
        self::assertEquals($video, $item->videoLinkIdentifier());
        self::assertSame(VideoProvider::YOUTUBE, $item->provider());
        self::assertSame('abcdefghijk', (string) $item->externalVideoId());
        self::assertSame('https://www.youtube.com/watch?v=abcdefghijk', (string) $item->url());
        self::assertSame('Server title', (string) $item->title());
        self::assertNull($item->thumbnailUrl());
        self::assertNull($item->durationSeconds());
        self::assertSame(0, $item->position()->value());
    }

    public function testMissingSourceReturnsNotFound(): void
    {
        [$owner, $video, $playlist] = $this->fixture();
        DB::table('wiki_video_links')->delete();
        $this->expectException(VideoLinkNotFoundException::class);
        $this->app()->make(PlaylistVideoSourceServiceInterface::class)->resolveMany([$video], $owner, $playlist);
    }

    public function testUnpublishedSourceReturnsNotFoundEvenWhenNotLiked(): void
    {
        [$owner, $video, $playlist] = $this->fixture();
        DB::table('wikis')->update(['published_at' => null]);
        DB::table('wiki_video_link_likes')->delete();
        $this->expectException(VideoLinkNotFoundException::class);
        $this->app()->make(PlaylistVideoSourceServiceInterface::class)->resolveMany([$video], $owner, $playlist);
    }

    public function testRequiresLikeByThisPrincipal(): void
    {
        [$owner, $video, $playlist] = $this->fixture();
        DB::table('wiki_video_link_likes')->delete();
        $this->expectException(DisallowedException::class);
        $this->app()->make(PlaylistVideoSourceServiceInterface::class)->resolveMany([$video], $owner, $playlist);
    }

    public function testResolvesMultipleDistinctSourcesWithTwoQueries(): void
    {
        [$owner, $first, $playlist] = $this->fixture();
        [, $second] = $this->fixture();
        DB::table('wiki_video_link_likes')->where('video_link_id', (string) $second)->update(['principal_id' => (string) $owner]);
        $service = $this->app()->make(PlaylistVideoSourceServiceInterface::class);

        DB::enableQueryLog();
        DB::flushQueryLog();

        try {
            $items = $service->resolveMany([$second, $first, $second], $owner, $playlist);
            self::assertCount(2, DB::getQueryLog());
        } finally {
            DB::disableQueryLog();
            DB::flushQueryLog();
        }

        self::assertSame([(string) $second, (string) $first], array_keys($items));
        self::assertEquals($second, $items[(string) $second]->videoLinkIdentifier());
        self::assertEquals($first, $items[(string) $first]->videoLinkIdentifier());
    }

    public function testEmptyInputDoesNotQueryDatabase(): void
    {
        $service = $this->app()->make(PlaylistVideoSourceServiceInterface::class);
        $owner = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $playlist = new PlaylistIdentifier(StrTestHelper::generateUuid());
        DB::enableQueryLog();
        DB::flushQueryLog();

        try {
            self::assertSame([], $service->resolveMany([], $owner, $playlist));
            self::assertSame([], DB::getQueryLog());
        } finally {
            DB::disableQueryLog();
            DB::flushQueryLog();
        }
    }

    public function testRejectsBatchContainingMissingSource(): void
    {
        [$owner, $video, $playlist] = $this->fixture();
        $missing = new VideoLinkIdentifier(StrTestHelper::generateUuid());
        $this->expectException(VideoLinkNotFoundException::class);
        $this->app()->make(PlaylistVideoSourceServiceInterface::class)->resolveMany([$video, $missing], $owner, $playlist);
    }

    public function testRejectsBatchContainingSourceLikedOnlyByAnotherPrincipal(): void
    {
        [$owner, $video, $playlist] = $this->fixture();
        [, $otherVideo] = $this->fixture();
        $this->expectException(DisallowedException::class);
        $this->app()->make(PlaylistVideoSourceServiceInterface::class)->resolveMany([$video, $otherVideo], $owner, $playlist);
    }

    /** @return array{PrincipalIdentifier, VideoLinkIdentifier, PlaylistIdentifier} */
    private function fixture(): array
    {
        $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
        $owner = new PrincipalIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identity, ['email' => (string) $identity . '@example.com']);
        CreatePrincipal::create($owner, $identity);
        $wiki = StrTestHelper::generateUuid();
        CreateWiki::create($wiki, 'talent', ['slug' => 'tl-' . $wiki, 'published_at' => '2026-01-01']);
        $video = new VideoLinkIdentifier(StrTestHelper::generateUuid());
        CreateVideoLink::create((string) $video, ['wiki_id' => $wiki, 'url' => 'https://youtu.be/abcdefghijk', 'title' => 'Server title']);
        DB::table('wiki_video_link_likes')->insert(['id' => StrTestHelper::generateUuid(), 'video_link_id' => (string) $video, 'principal_id' => (string) $owner, 'created_at' => '2026-01-01']);

        return [$owner, $video, new PlaylistIdentifier(StrTestHelper::generateUuid())];
    }
}
