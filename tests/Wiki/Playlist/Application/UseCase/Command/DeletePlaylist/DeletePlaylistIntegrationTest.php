<?php

declare(strict_types=1);

namespace Tests\Wiki\Playlist\Application\UseCase\Command\DeletePlaylist;

use Database\Seeders\SystemPolicySeeder;
use Database\Seeders\SystemRoleSeeder;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Playlist\Application\UseCase\Command\CreatePlaylist\CreatePlaylistInput;
use Source\Wiki\Playlist\Application\UseCase\Command\CreatePlaylist\CreatePlaylistInterface;
use Source\Wiki\Playlist\Application\UseCase\Command\CreatePlaylist\CreatePlaylistOutput;
use Source\Wiki\Playlist\Application\UseCase\Command\DeletePlaylist\DeletePlaylistInput;
use Source\Wiki\Playlist\Application\UseCase\Command\DeletePlaylist\DeletePlaylistInterface;
use Source\Wiki\Playlist\Application\UseCase\Query\GetMyPlaylist\GetMyPlaylistInput;
use Source\Wiki\Playlist\Application\UseCase\Query\GetMyPlaylist\GetMyPlaylistInterface;
use Source\Wiki\Playlist\Application\UseCase\Query\GetMyPlaylist\GetMyPlaylistOutput;
use Source\Wiki\Playlist\Application\UseCase\Query\ListMyPlaylists\ListMyPlaylistsInput;
use Source\Wiki\Playlist\Application\UseCase\Query\ListMyPlaylists\ListMyPlaylistsInterface;
use Source\Wiki\Playlist\Application\UseCase\Query\ListMyPlaylists\ListMyPlaylistsOutput;
use Source\Wiki\Playlist\Domain\Exception\PlaylistLimitExceededException;
use Source\Wiki\Playlist\Domain\Exception\PlaylistNotFoundException;
use Source\Wiki\Playlist\Domain\Repository\PlaylistRepositoryInterface;
use Source\Wiki\Playlist\Domain\ValueObject\PlaylistIdentifier;
use Source\Wiki\Playlist\Domain\ValueObject\PlaylistName;
use Source\Wiki\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\VideoLinkIdentifier;
use Tests\Helper\CreateAccount;
use Tests\Helper\CreateIdentity;
use Tests\Helper\CreatePrincipal;
use Tests\Helper\CreatePrincipalGroup;
use Tests\Helper\CreatePrincipalGroupMembership;
use Tests\Helper\CreateVideoLink;
use Tests\Helper\PlaylistTestHelper;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class DeletePlaylistIntegrationTest extends TestCase
{
    /** @return iterable<string, array{string, bool}> */
    public static function roles(): iterable
    {
        foreach (['Collaborator', 'SeniorCollaborator', 'Operator', 'AgencyActor', 'TalentActor', 'None', 'Administrator'] as $role) {
            yield $role => [$role, ! in_array($role, ['None', 'Administrator'], true)];
        }
    }

    #[DataProvider('roles')]
    public function testSeededRolesDeleteOnlyWhenPermitted(string $role, bool $allowed): void
    {
        $this->seed([SystemPolicySeeder::class, SystemRoleSeeder::class]);
        $owner = $this->principal($role);
        $playlist = PlaylistTestHelper::playlist(owner: $owner);
        $repository = $this->app()->make(PlaylistRepositoryInterface::class);
        $repository->save($playlist);

        try {
            $this->app()->make(DeletePlaylistInterface::class)->process(new DeletePlaylistInput($owner, $playlist->playlistIdentifier()));
            self::assertTrue($allowed, 'An unauthorized role deleted the playlist.');
            self::assertNull($repository->findByIdAndPrincipal($playlist->playlistIdentifier(), $owner));
        } catch (DisallowedException) {
            self::assertFalse($allowed);
            self::assertNotNull($repository->findByIdAndPrincipal($playlist->playlistIdentifier(), $owner));
        }
    }

    public function testDeletesFiftyItemsButPreservesVideosLikesOtherListsAndRestoresQuota(): void
    {
        $this->seed([SystemPolicySeeder::class, SystemRoleSeeder::class]);
        $owner = $this->principal();
        $other = $this->principal();
        $id = new PlaylistIdentifier(StrTestHelper::generateUuid());
        $video = new VideoLinkIdentifier(StrTestHelper::generateUuid());
        CreateVideoLink::create((string) $video);
        $like = ['id' => StrTestHelper::generateUuid(), 'video_link_id' => (string) $video, 'principal_id' => (string) $owner, 'created_at' => '2026-01-01 00:00:00'];
        DB::table('wiki_video_link_likes')->insert($like);
        $items = [];
        for ($position = 0; $position < 50; ++$position) {
            $items[] = PlaylistTestHelper::item($id, $position, $position === 0 ? $video : null);
        }
        $repository = $this->app()->make(PlaylistRepositoryInterface::class);
        $repository->save(PlaylistTestHelper::playlist($id, $owner, $items));
        $retained = [];
        foreach ([$owner, $owner, $other] as $principal) {
            $playlist = PlaylistTestHelper::playlist(owner: $principal);
            $repository->save($playlist);
            $retained[] = $playlist;
        }
        $createPlaylist = $this->app()->make(CreatePlaylistInterface::class);
        $createInput = new CreatePlaylistInput($owner, new PlaylistName('New playlist'));

        try {
            $createPlaylist->process($createInput, new CreatePlaylistOutput());
            self::fail('Fourth playlist was accepted.');
        } catch (PlaylistLimitExceededException) {
            self::assertSame(3, $repository->countByPrincipal($owner));
        }
        $this->app()->make(DeletePlaylistInterface::class)->process(new DeletePlaylistInput($owner, $id));
        self::assertNull($repository->findByIdAndPrincipal($id, $owner));
        self::assertSame(0, DB::table('wiki_playlist_items')->where('playlist_id', (string) $id)->count());
        self::assertSame(1, DB::table('wiki_video_links')->where('id', (string) $video)->count());
        $this->assertDatabaseHas('wiki_video_link_likes', $like);
        foreach ($retained as $playlist) {
            self::assertNotNull($repository->findByIdAndPrincipal($playlist->playlistIdentifier(), $playlist->principalIdentifier()));
        }
        $listOutput = new ListMyPlaylistsOutput();
        $this->app()->make(ListMyPlaylistsInterface::class)->process(new ListMyPlaylistsInput($owner), $listOutput);
        $listed = array_column($listOutput->toArray()['playlists'], 'playlistIdentifier');
        self::assertCount(2, $listed);
        self::assertNotContains((string) $id, $listed);

        try {
            $this->app()->make(GetMyPlaylistInterface::class)->process(new GetMyPlaylistInput($owner, $id), new GetMyPlaylistOutput());
            self::fail('Deleted playlist was returned.');
        } catch (PlaylistNotFoundException) {
            self::assertSame(2, $repository->countByPrincipal($owner));
        }
        $createPlaylist->process($createInput, new CreatePlaylistOutput());
        self::assertSame(3, $repository->countByPrincipal($owner));
        self::assertSame(1, $repository->countByPrincipal($other));
    }

    /** @return array<string, array{string}> */
    public static function missingTargets(): array
    {
        return ['foreign' => ['foreign'], 'absent' => ['absent'], 'deleted' => ['deleted']];
    }

    #[DataProvider('missingTargets')]
    public function testForeignMissingAndAlreadyDeletedTargetsAreIndistinguishable(string $target): void
    {
        $this->seed([SystemPolicySeeder::class, SystemRoleSeeder::class]);
        $owner = $this->principal();
        $playlist = PlaylistTestHelper::playlist(owner: $this->principal());
        $repository = $this->app()->make(PlaylistRepositoryInterface::class);
        $repository->save($playlist);
        $id = $playlist->playlistIdentifier();
        if ($target === 'absent') {
            $id = new PlaylistIdentifier(StrTestHelper::generateUuid());
        } elseif ($target === 'deleted') {
            $ownPlaylist = PlaylistTestHelper::playlist(owner: $owner);
            $repository->save($ownPlaylist);
            $id = $ownPlaylist->playlistIdentifier();
            $this->app()->make(DeletePlaylistInterface::class)->process(new DeletePlaylistInput($owner, $id));
        }

        try {
            $this->app()->make(DeletePlaylistInterface::class)->process(new DeletePlaylistInput($owner, $id));
            self::fail('An inaccessible playlist was accepted.');
        } catch (PlaylistNotFoundException) {
            self::assertNotNull($repository->findByIdAndPrincipal($playlist->playlistIdentifier(), $playlist->principalIdentifier()));
            self::assertSame(1, DB::table('wiki_playlists')->count());
        }
    }

    /** @return array<string, array{string}> */
    public static function inactiveStatuses(): array
    {
        return ['suspended' => ['suspended'], 'forced stop' => ['forced_stop']];
    }

    #[DataProvider('inactiveStatuses')]
    public function testInactivePrincipalCannotDelete(string $status): void
    {
        $this->seed([SystemPolicySeeder::class, SystemRoleSeeder::class]);
        $owner = $this->principal();
        $playlist = PlaylistTestHelper::playlist(owner: $owner);
        $repository = $this->app()->make(PlaylistRepositoryInterface::class);
        $repository->save($playlist);
        DB::table('wiki_principals')->where('id', (string) $owner)->update(['status' => $status]);

        try {
            $this->app()->make(DeletePlaylistInterface::class)->process(new DeletePlaylistInput($owner, $playlist->playlistIdentifier()));
            self::fail('Inactive principal deleted a playlist.');
        } catch (DisallowedException) {
            self::assertNotNull($repository->findByIdAndPrincipal($playlist->playlistIdentifier(), $owner));
        }
    }

    private function principal(string $role = 'Collaborator'): PrincipalIdentifier
    {
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
