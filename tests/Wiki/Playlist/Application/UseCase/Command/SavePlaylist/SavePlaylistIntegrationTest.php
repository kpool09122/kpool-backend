<?php

declare(strict_types=1);

namespace Tests\Wiki\Playlist\Application\UseCase\Command\SavePlaylist;

use Application\Models\Wiki\PlaylistItem as PlaylistItemModel;
use Database\Seeders\SystemPolicySeeder;
use Database\Seeders\SystemRoleSeeder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use RuntimeException;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Playlist\Application\UseCase\Command\SavePlaylist\SavePlaylistInput;
use Source\Wiki\Playlist\Application\UseCase\Command\SavePlaylist\SavePlaylistInterface;
use Source\Wiki\Playlist\Application\UseCase\Command\SavePlaylist\SavePlaylistItemInput;
use Source\Wiki\Playlist\Application\UseCase\Query\GetMyPlaylist\GetMyPlaylistInput;
use Source\Wiki\Playlist\Application\UseCase\Query\GetMyPlaylist\GetMyPlaylistInterface;
use Source\Wiki\Playlist\Application\UseCase\Query\GetMyPlaylist\GetMyPlaylistOutput;
use Source\Wiki\Playlist\Application\UseCase\Query\ListMyPlaylists\ListMyPlaylistsInput;
use Source\Wiki\Playlist\Application\UseCase\Query\ListMyPlaylists\ListMyPlaylistsInterface;
use Source\Wiki\Playlist\Application\UseCase\Query\ListMyPlaylists\ListMyPlaylistsOutput;
use Source\Wiki\Playlist\Domain\Entity\Playlist;
use Source\Wiki\Playlist\Domain\Exception\PlaylistItemLimitExceededException;
use Source\Wiki\Playlist\Domain\Exception\PlaylistNotFoundException;
use Source\Wiki\Playlist\Domain\Repository\PlaylistRepositoryInterface;
use Source\Wiki\Playlist\Domain\ValueObject\ExternalVideoId;
use Source\Wiki\Playlist\Domain\ValueObject\PlaylistName;
use Source\Wiki\Playlist\Domain\ValueObject\VideoProvider;
use Source\Wiki\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\VideoLinkIdentifier;
use Source\Wiki\VideoLink\Application\Exception\VideoLinkNotFoundException;
use Tests\Helper\CreateAccount;
use Tests\Helper\CreateIdentity;
use Tests\Helper\CreatePrincipal;
use Tests\Helper\CreatePrincipalGroup;
use Tests\Helper\CreatePrincipalGroupMembership;
use Tests\Helper\CreateVideoLink;
use Tests\Helper\CreateWiki;
use Tests\Helper\PlaylistTestHelper;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;
use Throwable;

#[Group('useDb')]
class SavePlaylistIntegrationTest extends TestCase
{
    public function testSavesNameMembershipOrderAndPreservesSnapshotsOnRetry(): void
    {
        $owner = $this->principal();
        $playlist = PlaylistTestHelper::playlist(owner: $owner);
        $first = PlaylistTestHelper::item($playlist->playlistIdentifier(), 0);
        $removed = PlaylistTestHelper::item($playlist->playlistIdentifier(), 1);
        $repository = $this->app()->make(PlaylistRepositoryInterface::class);
        $repository->save(PlaylistTestHelper::playlist($playlist->playlistIdentifier(), $owner, [$first, $removed]));
        $video = $this->video($owner, 'abcdefghijk');
        $items = [new SavePlaylistItemInput(videoLinkIdentifier: $video), new SavePlaylistItemInput(provider: $first->provider(), externalVideoId: $first->externalVideoId())];
        $this->save($owner, $playlist, $items);
        $saved = $repository->findByIdAndPrincipal($playlist->playlistIdentifier(), $owner);
        self::assertNotNull($saved);
        self::assertSame('Saved', (string) $saved->name());
        self::assertEquals($playlist->createdAt(), $saved->createdAt());
        self::assertSame('abcdefghijk', (string) $saved->items()[0]->externalVideoId());
        self::assertSame('Server title', (string) $saved->items()[0]->title());
        self::assertSame('https://www.youtube.com/watch?v=abcdefghijk', (string) $saved->items()[0]->url());
        self::assertSame('https://example.com/thumb.jpg', (string) $saved->items()[0]->thumbnailUrl());
        self::assertSame(123, $saved->items()[0]->durationSeconds()?->value());
        self::assertSame((string) $video, (string) $saved->items()[0]->videoLinkIdentifier());
        self::assertSame((string) $first->externalVideoId(), (string) $saved->items()[1]->externalVideoId());
        self::assertSame([0, 1], array_map(static fn ($item): int => $item->position()->value(), $saved->items()));
        DB::table('wiki_video_links')->where('id', (string) $video)->update(['title' => 'Changed source']);
        $this->save($owner, $playlist, $items);
        self::assertEquals($saved->items(), $repository->findByIdAndPrincipal($playlist->playlistIdentifier(), $owner)?->items());
        $detail = new GetMyPlaylistOutput();
        $this->app()->make(GetMyPlaylistInterface::class)->process(new GetMyPlaylistInput($owner, $playlist->playlistIdentifier()), $detail);
        $detailResult = $detail->toArray();
        self::assertSame('Saved', $detailResult['name'] ?? null);
        self::assertSame(array_map(static fn ($item): string => (string) $item->externalVideoId(), $saved->items()), array_column($detailResult['items'] ?? [], 'externalVideoId'));
        $list = new ListMyPlaylistsOutput();
        $this->app()->make(ListMyPlaylistsInterface::class)->process(new ListMyPlaylistsInput($owner), $list);
        self::assertSame(2, $list->toArray()['playlists'][0]['videoCount']);
        $this->save($owner, $playlist, []);
        $this->save($owner, $playlist, []);
        self::assertSame(0, $repository->findByIdAndPrincipal($playlist->playlistIdentifier(), $owner)?->videoCount());
        self::assertSame(1, DB::table('wiki_video_links')->count());
        self::assertSame(1, DB::table('wiki_video_link_likes')->count());
    }

    /** @return array<string, array{string}> */
    public static function unavailableSources(): array
    {
        return ['deleted' => ['deleted'], 'unpublished' => ['unpublished'], 'unliked' => ['unliked']];
    }

    #[DataProvider('unavailableSources')]
    public function testKeepsAndReordersExistingItemsWithoutRevalidatingSource(string $condition): void
    {
        $owner = $this->principal();
        $video = $this->video($owner, 'abcdefghijk');
        $playlist = PlaylistTestHelper::playlist(owner: $owner);
        $first = PlaylistTestHelper::item($playlist->playlistIdentifier(), 0, $video, 'abcdefghijk');
        $second = PlaylistTestHelper::item($playlist->playlistIdentifier(), 1);
        $repository = $this->app()->make(PlaylistRepositoryInterface::class);
        $repository->save(PlaylistTestHelper::playlist($playlist->playlistIdentifier(), $owner, [$first, $second]));
        if ($condition === 'deleted') {
            DB::table('wiki_video_links')->where('id', (string) $video)->delete();
        } elseif ($condition === 'unpublished') {
            DB::table('wikis')->update(['published_at' => null]);
        } else {
            DB::table('wiki_video_link_likes')->delete();
        }
        $this->save($owner, $playlist, [new SavePlaylistItemInput(provider: $second->provider(), externalVideoId: $second->externalVideoId()), new SavePlaylistItemInput(provider: $first->provider(), externalVideoId: $first->externalVideoId())]);
        $saved = $repository->findByIdAndPrincipal($playlist->playlistIdentifier(), $owner);
        self::assertNotNull($saved);
        self::assertSame((string) $first->externalVideoId(), (string) $saved->items()[1]->externalVideoId());
        self::assertSame((string) $first->title(), (string) $saved->items()[1]->title());
        self::assertSame(1, $saved->items()[1]->position()->value());
        if ($condition === 'deleted') {
            self::assertNull($saved->items()[1]->videoLinkIdentifier());
        }
    }

    /** @return array<string, array{string, class-string<Throwable>}> */
    public static function invalidEdits(): array
    {
        return [
            'foreign playlist' => ['foreignPlaylist', PlaylistNotFoundException::class],
            'absent playlist' => ['missingPlaylist', PlaylistNotFoundException::class],
            'foreign item' => ['foreignItem', PlaylistNotFoundException::class],
            'absent item' => ['missingItem', PlaylistNotFoundException::class],
            'missing source' => ['missingSource', VideoLinkNotFoundException::class],
            'unpublished source' => ['unpublished', VideoLinkNotFoundException::class],
            'missing wiki' => ['missingWiki', VideoLinkNotFoundException::class],
            'not liked by actor' => ['unliked', DisallowedException::class],
            'same item twice' => ['duplicateItem', InvalidArgumentException::class],
            'same source twice' => ['duplicateSource', InvalidArgumentException::class],
            'translated source duplicate' => ['translatedDuplicate', InvalidArgumentException::class],
            'existing and source duplicate' => ['existingDuplicate', InvalidArgumentException::class],
            'fifty one' => ['tooMany', PlaylistItemLimitExceededException::class],
        ];
    }

    /** @param class-string<Throwable> $exception */
    #[DataProvider('invalidEdits')]
    public function testRejectsEntireInvalidEditWithoutChangingStoredState(string $case, string $exception): void
    {
        $owner = $this->principal();
        $other = $this->principal();
        $playlist = PlaylistTestHelper::playlist(owner: $owner);
        $item = PlaylistTestHelper::item($playlist->playlistIdentifier(), 0, externalId: 'abcdefghijk');
        $playlist = PlaylistTestHelper::playlist($playlist->playlistIdentifier(), $owner, [$item]);
        $foreign = PlaylistTestHelper::playlist(owner: $other);
        $foreignItem = PlaylistTestHelper::item($foreign->playlistIdentifier(), 0);
        $repository = $this->app()->make(PlaylistRepositoryInterface::class);
        $repository->save($playlist);
        $repository->save(PlaylistTestHelper::playlist($foreign->playlistIdentifier(), $other, [$foreignItem]));
        $selection = new SavePlaylistItemInput(videoLinkIdentifier: $this->video($owner, 'abcdefghijk'));
        $target = $playlist;
        $items = [$selection];
        switch ($case) {
            case 'foreignPlaylist': $target = $foreign;

                break;
            case 'missingPlaylist': $target = PlaylistTestHelper::playlist(owner: $owner);

                break;
            case 'foreignItem': $items = [new SavePlaylistItemInput(provider: $foreignItem->provider(), externalVideoId: $foreignItem->externalVideoId())];

                break;
            case 'missingItem': $items = [new SavePlaylistItemInput(provider: VideoProvider::YOUTUBE, externalVideoId: new ExternalVideoId('zzzzzzzzzzz'))];

                break;
            case 'missingSource': $items = [new SavePlaylistItemInput(videoLinkIdentifier: new VideoLinkIdentifier(StrTestHelper::generateUuid()))];

                break;
            case 'unpublished': DB::table('wikis')->update(['published_at' => null]);

                break;
            case 'missingWiki': DB::table('wikis')->delete();

                break;
            case 'unliked': DB::table('wiki_video_link_likes')->update(['principal_id' => (string) $other]);

                break;
            case 'duplicateItem': $items = [new SavePlaylistItemInput(provider: $item->provider(), externalVideoId: $item->externalVideoId()), new SavePlaylistItemInput(provider: $item->provider(), externalVideoId: $item->externalVideoId())];

                break;
            case 'duplicateSource': $items = [$selection, $selection];

                break;
            case 'translatedDuplicate': $items = [$selection, new SavePlaylistItemInput(videoLinkIdentifier: $this->video($owner, 'abcdefghijk', 'ja'))];

                break;
            case 'existingDuplicate': $items = [new SavePlaylistItemInput(provider: $item->provider(), externalVideoId: $item->externalVideoId()), $selection];

                break;
            case 'tooMany': $items = array_fill(0, 51, $selection);

                break;
        }

        try {
            $this->save($owner, $target, $items);
            self::fail('Invalid edit succeeded.');
        } catch (Throwable $e) {
            self::assertInstanceOf($exception, $e);
            self::assertEquals($playlist, $repository->findByIdAndPrincipal($playlist->playlistIdentifier(), $owner));
            self::assertNotNull($repository->findByIdAndPrincipal($foreign->playlistIdentifier(), $other));
        }
    }

    public function testSavesFiftyDistinctVideosAndAllowsSameVideoInAnotherPlaylist(): void
    {
        $owner = $this->principal();
        $playlist = PlaylistTestHelper::playlist(owner: $owner);
        $repository = $this->app()->make(PlaylistRepositoryInterface::class);
        $repository->save($playlist);
        $items = [];
        for ($i = 0; $i < 50; ++$i) {
            $items[] = new SavePlaylistItemInput(videoLinkIdentifier: $this->video($owner, str_pad((string) $i, 11, '0', STR_PAD_LEFT)));
        }
        DB::enableQueryLog();
        DB::flushQueryLog();

        try {
            $this->save($owner, $playlist, $items);
            $sourceQueries = array_filter(DB::getQueryLog(), static fn (array $query): bool => str_contains($query['query'], 'wiki_video_link'));
            self::assertCount(2, $sourceQueries);
        } finally {
            DB::disableQueryLog();
            DB::flushQueryLog();
        }
        $saved = $repository->findByIdAndPrincipal($playlist->playlistIdentifier(), $owner);
        self::assertNotNull($saved);
        self::assertSame(50, $saved->videoCount());
        self::assertSame(
            array_map(static fn (SavePlaylistItemInput $item): string => (string) $item->videoLinkIdentifier, $items),
            array_map(static fn ($item): string => (string) $item->videoLinkIdentifier(), $saved->items()),
        );
        $another = PlaylistTestHelper::playlist(owner: $owner);
        $repository->save($another);
        $this->save($owner, $another, [$items[0]]);
        $this->save($owner, $playlist, []);
        self::assertSame(1, $repository->findByIdAndPrincipal($another->playlistIdentifier(), $owner)?->videoCount());
        self::assertSame(50, DB::table('wiki_video_links')->count());
        self::assertSame(50, DB::table('wiki_video_link_likes')->count());
    }

    public function testRollsBackNameAndItemsAfterAnInsertFailsFollowingAnEarlierWrite(): void
    {
        $owner = $this->principal();
        $playlist = PlaylistTestHelper::playlist(owner: $owner);
        $first = PlaylistTestHelper::item($playlist->playlistIdentifier(), 0);
        $second = PlaylistTestHelper::item($playlist->playlistIdentifier(), 1);
        $playlist = PlaylistTestHelper::playlist($playlist->playlistIdentifier(), $owner, [$first, $second]);
        $repository = $this->app()->make(PlaylistRepositoryInterface::class);
        $repository->save($playlist);
        $observedEarlierWrite = false;
        PlaylistItemModel::creating(static function (PlaylistItemModel $model) use (&$observedEarlierWrite): void {
            if ($model->position === 1) {
                $observedEarlierWrite = DB::table('wiki_playlists')->where('id', $model->playlist_id)->value('name') === 'Saved'
                    && DB::table('wiki_playlist_items')->where('playlist_id', $model->playlist_id)->count() === 1;

                throw new RuntimeException('Injected second insert failure');
            }
        });

        try {
            $this->save($owner, $playlist, [new SavePlaylistItemInput(provider: $second->provider(), externalVideoId: $second->externalVideoId()), new SavePlaylistItemInput(provider: $first->provider(), externalVideoId: $first->externalVideoId())]);
            self::fail('Injected failure did not occur.');
        } catch (RuntimeException $e) {
            self::assertSame('Injected second insert failure', $e->getMessage());
        } finally {
            PlaylistItemModel::flushEventListeners();
        }
        self::assertTrue($observedEarlierWrite);
        self::assertEquals($playlist, $repository->findByIdAndPrincipal($playlist->playlistIdentifier(), $owner));
    }

    public function testMissingPrincipalCannotSave(): void
    {
        $playlist = PlaylistTestHelper::playlist();
        $this->expectException(PrincipalNotFoundException::class);
        $this->save($playlist->principalIdentifier(), $playlist, []);
    }

    /** @return array<string, array{string, ?string}> */
    public static function deniedActors(): array
    {
        return ['no role' => ['None', null], 'administrator' => ['Administrator', null], 'suspended' => ['Collaborator', 'suspended'], 'forced stop' => ['Collaborator', 'forced_stop']];
    }

    #[DataProvider('deniedActors')]
    public function testDeniesUnauthorizedAndInactiveActors(string $role, ?string $status): void
    {
        $owner = $this->principal($role);
        $playlist = PlaylistTestHelper::playlist(owner: $owner);
        $repository = $this->app()->make(PlaylistRepositoryInterface::class);
        $repository->save($playlist);
        if ($status !== null) {
            DB::table('wiki_principals')->where('id', (string) $owner)->update(['status' => $status]);
        }

        try {
            $this->save($owner, $playlist, []);
            self::fail('Unauthorized edit succeeded.');
        } catch (DisallowedException) {
            self::assertEquals($playlist, $repository->findByIdAndPrincipal($playlist->playlistIdentifier(), $owner));
        }
    }

    /** @param list<SavePlaylistItemInput> $items */
    private function save(PrincipalIdentifier $owner, Playlist $playlist, array $items): void
    {
        DB::transaction(fn () => $this->app()->make(SavePlaylistInterface::class)->process(new SavePlaylistInput($owner, $playlist->playlistIdentifier(), new PlaylistName(' Saved '), $items)));
    }

    private function video(PrincipalIdentifier $owner, string $externalId, string $language = 'ko'): VideoLinkIdentifier
    {
        $wiki = StrTestHelper::generateUuid();
        CreateWiki::create($wiki, 'talent', ['slug' => 'tl-' . $wiki, 'language' => $language, 'published_at' => '2026-01-01 00:00:00']);
        $video = new VideoLinkIdentifier(StrTestHelper::generateUuid());
        CreateVideoLink::create((string) $video, ['wiki_id' => $wiki, 'url' => 'https://www.youtube.com/watch?v=' . $externalId, 'title' => 'Server title', 'thumbnail_url' => 'https://example.com/thumb.jpg', 'duration_seconds' => 123]);
        DB::table('wiki_video_link_likes')->insert(['id' => StrTestHelper::generateUuid(), 'video_link_id' => (string) $video, 'principal_id' => (string) $owner, 'created_at' => '2026-01-01 00:00:00']);

        return $video;
    }

    private function principal(string $role = 'Collaborator'): PrincipalIdentifier
    {
        $this->seed([SystemPolicySeeder::class, SystemRoleSeeder::class]);
        $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
        $account = new AccountIdentifier(StrTestHelper::generateUuid());
        $principal = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $group = new PrincipalGroupIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identity, ['email' => (string) $identity . '@example.com']);
        CreateAccount::create((string) $account);
        CreatePrincipal::create($principal, $identity, $account);
        CreatePrincipalGroup::create($group, $account);
        CreatePrincipalGroupMembership::create((string) $group, (string) $principal);
        DB::table('wiki_principal_group_role_attachments')->insert(['principal_group_id' => (string) $group, 'role_id' => DB::table('wiki_roles')->where('name', $role)->value('id')]);

        return $principal;
    }
}
