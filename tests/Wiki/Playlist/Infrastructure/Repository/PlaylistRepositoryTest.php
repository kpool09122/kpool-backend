<?php

declare(strict_types=1);

namespace Tests\Wiki\Playlist\Infrastructure\Repository;

use Application\Models\Wiki\PlaylistItem as PlaylistItemModel;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Playlist\Domain\Factory\PlaylistFactoryInterface;
use Source\Wiki\Playlist\Domain\Repository\PlaylistRepositoryInterface;
use Source\Wiki\Playlist\Domain\ValueObject\PlaylistIdentifier;
use Source\Wiki\Playlist\Domain\ValueObject\PlaylistItem;
use Source\Wiki\Playlist\Domain\ValueObject\PlaylistItemPosition;
use Source\Wiki\Playlist\Domain\ValueObject\PlaylistName;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\VideoLinkIdentifier;
use Source\Wiki\VideoLink\Domain\Repository\VideoLinkRepositoryInterface;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Tests\Helper\CreateIdentity;
use Tests\Helper\CreatePrincipal;
use Tests\Helper\CreateVideoLink;
use Tests\Helper\PlaylistTestHelper;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class PlaylistRepositoryTest extends TestCase
{
    public function testItemTableHasNoSurrogateIdentifier(): void
    {
        self::assertFalse(Schema::hasColumn('wiki_playlist_items', 'id'));
    }

    public function testModelUpdatesAndDeletesOnlyMatchingCompositeKey(): void
    {
        $owner = $this->principal();
        $first = PlaylistTestHelper::playlist(owner: $owner);
        $second = PlaylistTestHelper::playlist(owner: $owner);
        $repository = $this->app()->make(PlaylistRepositoryInterface::class);
        foreach ([$first, $second] as $playlist) {
            $repository->save(PlaylistTestHelper::playlist($playlist->playlistIdentifier(), $owner, [PlaylistTestHelper::item($playlist->playlistIdentifier(), 0)]));
        }
        $item = PlaylistItemModel::query()->where('playlist_id', (string) $first->playlistIdentifier())->firstOrFail();
        $item->title = 'Changed';
        $item->save();
        $item->refresh();
        self::assertSame('Changed', $item->title);
        self::assertSame('Snapshot title', (string) $repository->findByIdAndPrincipal($second->playlistIdentifier(), $owner)?->items()[0]->title());
        $item->delete();
        self::assertSame(0, $repository->findByIdAndPrincipal($first->playlistIdentifier(), $owner)?->videoCount());
        self::assertSame(1, $repository->findByIdAndPrincipal($second->playlistIdentifier(), $owner)?->videoCount());
    }

    public function testCreatesEmptyPlaylistsWithSameNameAndScopesByOwner(): void
    {
        $owner = $this->principal();
        $other = $this->principal();
        $repository = $this->app()->make(PlaylistRepositoryInterface::class);
        $factory = $this->app()->make(PlaylistFactoryInterface::class);
        self::assertSame(0, $repository->countByPrincipal($owner));
        $playlist = $factory->create($owner, new PlaylistName('  My playlist  '));
        $repository->save($playlist);
        $repository->save($playlist);
        $repository->save($factory->create($owner, new PlaylistName('My playlist')));
        $repository->save($factory->create($other, new PlaylistName('My playlist')));
        self::assertSame(2, $repository->countByPrincipal($owner));
        self::assertSame(1, $repository->countByPrincipal($other));
        $restored = $repository->findByIdAndPrincipal($playlist->playlistIdentifier(), $owner);
        self::assertNotNull($restored);
        self::assertSame((string) $playlist->playlistIdentifier(), (string) $restored->playlistIdentifier());
        self::assertSame((string) $owner, (string) $restored->principalIdentifier());
        self::assertSame('My playlist', (string) $restored->name());
        self::assertSame(0, $restored->videoCount());
        self::assertSame($playlist->createdAt()->getTimestamp(), $restored->createdAt()->getTimestamp());
        self::assertSame($playlist->updatedAt()->getTimestamp(), $restored->updatedAt()->getTimestamp());
        self::assertNull($repository->findByIdAndPrincipal($playlist->playlistIdentifier(), $other));
        self::assertNull($repository->findByIdAndPrincipal(new PlaylistIdentifier(StrTestHelper::generateUuid()), $owner));
        $repository->deleteByIdAndPrincipal($playlist->playlistIdentifier(), $other);
        self::assertNotNull($repository->findByIdAndPrincipal($playlist->playlistIdentifier(), $owner));
        $repository->deleteByIdAndPrincipal($playlist->playlistIdentifier(), $owner);
        $repository->deleteByIdAndPrincipal($playlist->playlistIdentifier(), $owner);
        self::assertNull($repository->findByIdAndPrincipal($playlist->playlistIdentifier(), $owner));
        self::assertSame(1, $repository->countByPrincipal($owner));
        $repository->save($factory->create($owner, new PlaylistName('Again')));
        self::assertSame(2, $repository->countByPrincipal($owner));
    }

    public function testRoundTripsSnapshotAndPositionWithNullableSourceAndMetadata(): void
    {
        $owner = $this->principal();
        $id = new PlaylistIdentifier(StrTestHelper::generateUuid());
        $items = [PlaylistTestHelper::item($id, 1), PlaylistTestHelper::item($id, 0)];
        $playlist = PlaylistTestHelper::playlist($id, $owner, $items);
        $repository = $this->app()->make(PlaylistRepositoryInterface::class);
        $repository->save($playlist);
        $repository->save($playlist);
        $restored = $repository->findByIdAndPrincipal($id, $owner);
        self::assertNotNull($restored);
        self::assertCount(2, $restored->items());
        foreach ([$items[1], $items[0]] as $index => $expected) {
            $actual = $restored->items()[$index];
            self::assertSame((string) $id, (string) $actual->playlistIdentifier());
            self::assertNull($actual->videoLinkIdentifier());
            self::assertSame($expected->provider(), $actual->provider());
            self::assertSame((string) $expected->externalVideoId(), (string) $actual->externalVideoId());
            self::assertSame((string) $expected->url(), (string) $actual->url());
            self::assertSame((string) $expected->title(), (string) $actual->title());
            self::assertSame((string) $expected->thumbnailUrl(), (string) $actual->thumbnailUrl());
            self::assertEquals($expected->durationSeconds(), $actual->durationSeconds());
            self::assertSame($index, $actual->position()->value());
        }
    }

    public function testNullableSnapshotFieldsSurviveSaveAndReload(): void
    {
        $owner = $this->principal();
        $id = new PlaylistIdentifier(StrTestHelper::generateUuid());
        $item = PlaylistTestHelper::item($id, 0);
        $nullableItem = new PlaylistItem(
            $id,
            null,
            $item->provider(),
            $item->externalVideoId(),
            $item->url(),
            $item->title(),
            null,
            null,
            new PlaylistItemPosition(0),
        );
        $repository = $this->app()->make(PlaylistRepositoryInterface::class);
        $repository->save(PlaylistTestHelper::playlist($id, $owner, [$nullableItem]));
        $restored = $repository->findByIdAndPrincipal($id, $owner);
        self::assertNotNull($restored);
        self::assertCount(1, $restored->items());
        self::assertNull($restored->items()[0]->videoLinkIdentifier());
        self::assertNull($restored->items()[0]->thumbnailUrl());
        self::assertNull($restored->items()[0]->durationSeconds());
    }

    /** @return array<string, array{string}> */
    public static function deletionPaths(): array
    {
        return ['single' => ['single'], 'resource' => ['resource'], 'automatic' => ['automatic']];
    }

    #[DataProvider('deletionPaths')]
    public function testSourceDeletionRetainsItemSnapshotEvenAfterRecollection(string $path): void
    {
        $owner = $this->principal();
        $id = new PlaylistIdentifier(StrTestHelper::generateUuid());
        $video = new VideoLinkIdentifier(StrTestHelper::generateUuid());
        $wiki = new WikiIdentifier(StrTestHelper::generateUuid());
        CreateVideoLink::create((string) $video, ['wiki_id' => (string) $wiki, 'resource_type' => 'talent', 'video_usage' => 'youtube_auto_view_count']);
        $item = PlaylistTestHelper::item($id, 0, $video);
        $repository = $this->app()->make(PlaylistRepositoryInterface::class);
        $repository->save(PlaylistTestHelper::playlist($id, $owner, [$item]));
        $videoLinkRepository = $this->app()->make(VideoLinkRepositoryInterface::class);
        match ($path) {
            'single' => $videoLinkRepository->delete($video),
            'resource' => $videoLinkRepository->deleteByResource(ResourceType::TALENT, $wiki),
            default => $videoLinkRepository->deleteAutoCollectedByResource(ResourceType::TALENT, $wiki),
        };
        CreateVideoLink::create(StrTestHelper::generateUuid(), ['wiki_id' => (string) $wiki, 'url' => (string) $item->url()]);
        $restored = $repository->findByIdAndPrincipal($id, $owner);
        self::assertNotNull($restored);
        self::assertCount(1, $restored->items());
        self::assertNull($restored->items()[0]->videoLinkIdentifier());
        self::assertSame((string) $item->externalVideoId(), (string) $restored->items()[0]->externalVideoId());
        self::assertSame((string) $item->url(), (string) $restored->items()[0]->url());
        self::assertSame((string) $item->title(), (string) $restored->items()[0]->title());
        self::assertEquals($item->thumbnailUrl(), $restored->items()[0]->thumbnailUrl());
        self::assertEquals($item->durationSeconds(), $restored->items()[0]->durationSeconds());
        self::assertSame(0, $restored->items()[0]->position()->value());
    }

    /** @return array<string, array{string}> */
    public static function ownerDeletionPaths(): array
    {
        return ['principal' => ['principal'], 'playlist' => ['playlist']];
    }

    #[DataProvider('ownerDeletionPaths')]
    public function testOwnerOrPlaylistDeletionCascadesOnlyItsData(string $path): void
    {
        $owner = $this->principal();
        $other = $this->principal();
        $id = new PlaylistIdentifier(StrTestHelper::generateUuid());
        $otherId = new PlaylistIdentifier(StrTestHelper::generateUuid());
        $repository = $this->app()->make(PlaylistRepositoryInterface::class);
        $repository->save(PlaylistTestHelper::playlist($id, $owner, [PlaylistTestHelper::item($id, 0)]));
        $repository->save(PlaylistTestHelper::playlist($otherId, $other, [PlaylistTestHelper::item($otherId, 0)]));
        if ($path === 'principal') {
            DB::table('wiki_principals')->where('id', (string) $owner)->delete();
        } else {
            $repository->deleteByIdAndPrincipal($id, $owner);
        }
        self::assertNull($repository->findByIdAndPrincipal($id, $owner));
        self::assertSame(0, DB::table('wiki_playlist_items')->where('playlist_id', (string) $id)->count());
        self::assertNotNull($repository->findByIdAndPrincipal($otherId, $other));
        self::assertSame(1, DB::table('wiki_playlist_items')->where('playlist_id', (string) $otherId)->count());
    }

    public function testDatabaseRejectsDuplicateVideoWithinPlaylist(): void
    {
        $owner = $this->principal();
        $id = new PlaylistIdentifier(StrTestHelper::generateUuid());
        $repository = $this->app()->make(PlaylistRepositoryInterface::class);
        $repository->save(PlaylistTestHelper::playlist($id, $owner, [PlaylistTestHelper::item($id, 0)]));
        $row = (array) DB::table('wiki_playlist_items')->where('playlist_id', (string) $id)->first();
        $row['position'] = 1;
        $this->expectException(UniqueConstraintViolationException::class);
        DB::table('wiki_playlist_items')->insert($row);
    }

    private function principal(): PrincipalIdentifier
    {
        $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
        $principal = new PrincipalIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identity, ['email' => (string) $identity . '@example.com']);
        CreatePrincipal::create($principal, $identity);

        return $principal;
    }
}
