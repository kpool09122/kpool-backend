<?php

declare(strict_types=1);

namespace Tests\Wiki\Playlist\Infrastructure\Query;

use Database\Seeders\SystemPolicySeeder;
use Database\Seeders\SystemRoleSeeder;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Playlist\Application\UseCase\Query\ListMyPlaylists\ListMyPlaylistsInput;
use Source\Wiki\Playlist\Application\UseCase\Query\ListMyPlaylists\ListMyPlaylistsInterface;
use Source\Wiki\Playlist\Application\UseCase\Query\ListMyPlaylists\ListMyPlaylistsOutput;
use Source\Wiki\Playlist\Domain\Repository\PlaylistRepositoryInterface;
use Source\Wiki\Playlist\Domain\ValueObject\PlaylistIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
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
class ListMyPlaylistsTest extends TestCase
{
    public function testListsOnlyOwnedPlaylistsInStableOrderWithStoredItemCounts(): void
    {
        $this->seed([SystemPolicySeeder::class, SystemRoleSeeder::class]);
        $owner = $this->principal();
        $other = $this->principal();
        $repository = $this->app()->make(PlaylistRepositoryInterface::class);
        $ids = [
            '019a0000-0000-7000-8000-000000000003',
            '019a0000-0000-7000-8000-000000000002',
            '019a0000-0000-7000-8000-000000000001',
        ];
        foreach ($ids as $index => $value) {
            $id = new PlaylistIdentifier($value);
            $items = [];
            for ($position = 0; $position < $index; ++$position) {
                $items[] = PlaylistTestHelper::item($id, $position);
            }
            $repository->save(PlaylistTestHelper::playlist($id, $owner, $items));
            DB::table('wiki_playlists')->where('id', $value)->update([
                'created_at' => $index === 0 ? '2026-10-25 00:00:00' : '2026-10-26 00:00:00',
                'updated_at' => '2026-10-27 01:02:03',
            ]);
        }
        $repository->save(PlaylistTestHelper::playlist(owner: $other));
        $output = new ListMyPlaylistsOutput();
        $query = $this->app()->make(ListMyPlaylistsInterface::class);
        DB::enableQueryLog();
        DB::flushQueryLog();
        $query->process(new ListMyPlaylistsInput($owner), $output);
        $playlistQueries = array_filter(DB::getQueryLog(), static fn (array $query): bool => str_contains($query['query'], 'wiki_playlist'));
        DB::disableQueryLog();
        self::assertCount(1, $playlistQueries);
        $result = $output->toArray();
        self::assertSame([$ids[0], $ids[2], $ids[1]], array_column($result['playlists'], 'playlistIdentifier'));
        self::assertSame([0, 2, 1], array_column($result['playlists'], 'videoCount'));
        self::assertSame([
            'playlistIdentifier' => $ids[0],
            'name' => 'My playlist',
            'videoCount' => 0,
            'createdAt' => '2026-10-25T00:00:00+00:00',
            'updatedAt' => '2026-10-27T01:02:03+00:00',
        ], $result['playlists'][0]);
        $query->process(new ListMyPlaylistsInput($owner), $output);
        self::assertSame($result, $output->toArray());
    }

    public function testEmptyAndSinglePlaylistResponses(): void
    {
        $this->seed([SystemPolicySeeder::class, SystemRoleSeeder::class]);
        $owner = $this->principal();
        $query = $this->app()->make(ListMyPlaylistsInterface::class);
        $output = new ListMyPlaylistsOutput();
        $query->process(new ListMyPlaylistsInput($owner), $output);
        self::assertSame(['playlists' => []], $output->toArray());
        $playlist = PlaylistTestHelper::playlist(owner: $owner);
        $this->app()->make(PlaylistRepositoryInterface::class)->save($playlist);
        $query->process(new ListMyPlaylistsInput($owner), $output);
        self::assertSame(['playlists' => [[
            'playlistIdentifier' => (string) $playlist->playlistIdentifier(),
            'name' => 'My playlist',
            'videoCount' => 0,
            'createdAt' => '2026-10-26T00:00:00+00:00',
            'updatedAt' => '2026-10-26T00:00:00+00:00',
        ]]], $output->toArray());
    }

    public function testCountsFiftyStoredItemsAfterSourceVideoDeletion(): void
    {
        $this->seed([SystemPolicySeeder::class, SystemRoleSeeder::class]);
        $owner = $this->principal();
        $id = new PlaylistIdentifier(StrTestHelper::generateUuid());
        $video = new VideoLinkIdentifier(StrTestHelper::generateUuid());
        CreateVideoLink::create((string) $video);
        $items = [];
        for ($position = 0; $position < 50; ++$position) {
            $items[] = PlaylistTestHelper::item($id, $position, $video);
        }
        $this->app()->make(PlaylistRepositoryInterface::class)->save(PlaylistTestHelper::playlist($id, $owner, $items));
        $query = $this->app()->make(ListMyPlaylistsInterface::class);
        $output = new ListMyPlaylistsOutput();
        $query->process(new ListMyPlaylistsInput($owner), $output);
        $before = $output->toArray();
        self::assertSame(50, $before['playlists'][0]['videoCount']);
        DB::table('wiki_video_links')->where('id', (string) $video)->delete();
        self::assertSame(50, DB::table('wiki_playlist_items')->where('playlist_id', (string) $id)->whereNull('video_link_id')->count());
        $query->process(new ListMyPlaylistsInput($owner), $output);
        self::assertSame($before, $output->toArray());
    }

    /** @return iterable<string, array{string, bool}> */
    public static function roles(): iterable
    {
        foreach (['Collaborator', 'SeniorCollaborator', 'Operator', 'AgencyActor', 'TalentActor', 'None', 'Administrator'] as $role) {
            yield $role => [$role, ! in_array($role, ['None', 'Administrator'], true)];
        }
    }

    #[DataProvider('roles')]
    public function testSeededRoleAuthorization(string $role, bool $allowed): void
    {
        $this->seed([SystemPolicySeeder::class, SystemRoleSeeder::class]);
        $owner = $this->principal($role);
        $playlist = PlaylistTestHelper::playlist(owner: $owner);
        $this->app()->make(PlaylistRepositoryInterface::class)->save($playlist);
        $output = new ListMyPlaylistsOutput();
        $query = $this->app()->make(ListMyPlaylistsInterface::class);
        if (! $allowed) {
            try {
                $query->process(new ListMyPlaylistsInput($owner), $output);
                self::fail('Unauthorized read was accepted.');
            } catch (DisallowedException) {
                self::assertSame(['playlists' => []], $output->toArray());
            }

            return;
        }
        $query->process(new ListMyPlaylistsInput($owner), $output);
        self::assertSame((string) $playlist->playlistIdentifier(), $output->toArray()['playlists'][0]['playlistIdentifier']);
    }

    public function testMissingPrincipalIsRejected(): void
    {
        $this->expectException(PrincipalNotFoundException::class);
        $this->app()->make(ListMyPlaylistsInterface::class)->process(
            new ListMyPlaylistsInput(new PrincipalIdentifier(StrTestHelper::generateUuid())),
            new ListMyPlaylistsOutput(),
        );
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
        // 追加ロールの基本権限は、通常の利用者と同じくCollaboratorから取得する。
        $roles = in_array($role, ['SeniorCollaborator', 'AgencyActor', 'TalentActor'], true)
            ? ['Collaborator', $role]
            : [$role];
        foreach (DB::table('wiki_roles')->whereIn('name', $roles)->pluck('id') as $roleId) {
            DB::table('wiki_principal_group_role_attachments')->insert(['principal_group_id' => (string) $group, 'role_id' => $roleId]);
        }

        return $principal;
    }
}
