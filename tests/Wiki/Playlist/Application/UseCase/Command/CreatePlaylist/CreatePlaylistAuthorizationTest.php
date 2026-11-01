<?php

declare(strict_types=1);

namespace Tests\Wiki\Playlist\Application\UseCase\Command\CreatePlaylist;

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
use Source\Wiki\Playlist\Domain\Exception\PlaylistLimitExceededException;
use Source\Wiki\Playlist\Domain\Repository\PlaylistRepositoryInterface;
use Source\Wiki\Playlist\Domain\ValueObject\PlaylistIdentifier;
use Source\Wiki\Playlist\Domain\ValueObject\PlaylistName;
use Source\Wiki\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\CreateAccount;
use Tests\Helper\CreateIdentity;
use Tests\Helper\CreatePrincipal;
use Tests\Helper\CreatePrincipalGroup;
use Tests\Helper\CreatePrincipalGroupMembership;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class CreatePlaylistAuthorizationTest extends TestCase
{
    /** @return iterable<string, array{string, bool}> */
    public static function roles(): iterable
    {
        foreach (['Collaborator', 'SeniorCollaborator', 'Operator', 'AgencyActor', 'TalentActor', 'None', 'Administrator'] as $role) {
            yield $role => [$role, ! in_array($role, ['None', 'Administrator'], true)];
        }
    }

    #[DataProvider('roles')]
    public function testSeededRolePermissions(string $role, bool $allowed): void
    {
        $this->seed([SystemPolicySeeder::class, SystemRoleSeeder::class]);
        $owner = $this->principalWithRole($role);
        $input = new CreatePlaylistInput($owner, new PlaylistName('  My playlist  '));
        $output = new CreatePlaylistOutput();
        $createPlaylist = $this->app()->make(CreatePlaylistInterface::class);
        if (! $allowed) {
            try {
                $createPlaylist->process($input, $output);
                self::fail('Unauthorized creation was accepted.');
            } catch (DisallowedException) {
                self::assertSame(0, DB::table('wiki_playlists')->count());
                self::assertSame([], $output->toArray());
            }

            return;
        }
        $createPlaylist->process($input, $output);
        $result = $output->toArray();
        self::assertNotEmpty($result);
        self::assertArrayHasKey('playlistIdentifier', $result);
        $playlist = $this->app()->make(PlaylistRepositoryInterface::class)->findByIdAndPrincipal(new PlaylistIdentifier($result['playlistIdentifier'] ?? self::fail('Expected a created playlist identifier.')), $owner);
        self::assertNotNull($playlist);
        self::assertSame((string) $owner, (string) $playlist->principalIdentifier());
        self::assertSame('My playlist', (string) $playlist->name());
        self::assertSame(0, $playlist->videoCount());
    }

    public function testQuotaIsPersonalAndCreationResumesAfterDeletion(): void
    {
        $this->seed([SystemPolicySeeder::class, SystemRoleSeeder::class]);
        $owner = $this->principalWithRole('Collaborator');
        $other = $this->principalWithRole('Collaborator');
        $repository = $this->app()->make(PlaylistRepositoryInterface::class);
        $createPlaylist = $this->app()->make(CreatePlaylistInterface::class);
        $input = new CreatePlaylistInput($owner, new PlaylistName('Same name'));
        $output = new CreatePlaylistOutput();
        for ($count = 0; $count < 3; ++$count) {
            $createPlaylist->process($input, $output);
            self::assertSame($count + 1, $repository->countByPrincipal($owner));
        }

        try {
            $createPlaylist->process($input, new CreatePlaylistOutput());
            self::fail('Fourth playlist was accepted.');
        } catch (PlaylistLimitExceededException) {
            self::assertSame(3, $repository->countByPrincipal($owner));
        }
        $createPlaylist->process(new CreatePlaylistInput($other, new PlaylistName('Same name')), new CreatePlaylistOutput());
        self::assertSame(1, $repository->countByPrincipal($other));
        $result = $output->toArray();
        self::assertNotEmpty($result);
        self::assertArrayHasKey('playlistIdentifier', $result);
        $repository->deleteByIdAndPrincipal(new PlaylistIdentifier($result['playlistIdentifier'] ?? self::fail('Expected a created playlist identifier.')), $owner);
        $createPlaylist->process($input, new CreatePlaylistOutput());
        self::assertSame(3, $repository->countByPrincipal($owner));
    }

    private function principalWithRole(string $role): PrincipalIdentifier
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
