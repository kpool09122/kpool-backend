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
use Source\Wiki\Playlist\Application\UseCase\Query\GetMyPlaylist\GetMyPlaylistInput;
use Source\Wiki\Playlist\Application\UseCase\Query\GetMyPlaylist\GetMyPlaylistInterface;
use Source\Wiki\Playlist\Application\UseCase\Query\GetMyPlaylist\GetMyPlaylistOutput;
use Source\Wiki\Playlist\Domain\Exception\PlaylistNotFoundException;
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
use Tests\Helper\CreateWiki;
use Tests\Helper\PlaylistTestHelper;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class GetMyPlaylistTest extends TestCase
{
    public function testReturnsEmptyOwnedPlaylist(): void
    {
        $this->seed([SystemPolicySeeder::class, SystemRoleSeeder::class]);
        $owner = $this->principal();
        $playlist = PlaylistTestHelper::playlist(owner: $owner);
        $this->app()->make(PlaylistRepositoryInterface::class)->save($playlist);
        $output = new GetMyPlaylistOutput();
        $this->app()->make(GetMyPlaylistInterface::class)->process(new GetMyPlaylistInput($owner, $playlist->playlistIdentifier()), $output);
        self::assertSame([
            'playlistIdentifier' => (string) $playlist->playlistIdentifier(),
            'name' => 'My playlist',
            'videoCount' => 0,
            'createdAt' => '2026-10-26T00:00:00+00:00',
            'updatedAt' => '2026-10-26T00:00:00+00:00',
            'items' => [],
        ], $output->toArray());
    }

    public function testReturnsFiftySnapshotsInSavedOrderWithBoundedQueries(): void
    {
        $this->seed([SystemPolicySeeder::class, SystemRoleSeeder::class]);
        $owner = $this->principal();
        $id = new PlaylistIdentifier(StrTestHelper::generateUuid());
        $source = new VideoLinkIdentifier(StrTestHelper::generateUuid());
        CreateVideoLink::create((string) $source, ['title' => 'Current source is different']);
        $items = [];
        for ($position = 49; $position >= 0; --$position) {
            $items[] = PlaylistTestHelper::item($id, $position, $source);
        }
        $repository = $this->app()->make(PlaylistRepositoryInterface::class);
        $repository->save(PlaylistTestHelper::playlist($id, $owner, $items));
        $repository->save(PlaylistTestHelper::playlist(owner: $this->principal(), items: []));
        $query = $this->app()->make(GetMyPlaylistInterface::class);
        $output = new GetMyPlaylistOutput();
        DB::enableQueryLog();
        DB::flushQueryLog();
        $query->process(new GetMyPlaylistInput($owner, $id), $output);
        $queries = DB::getQueryLog();
        DB::disableQueryLog();
        $playlistQueries = array_filter($queries, static fn (array $query): bool => str_contains($query['query'], 'wiki_playlist'));
        self::assertCount(2, $playlistQueries);
        foreach ($queries as $sql) {
            self::assertStringNotContainsString('wiki_video_link', $sql['query']);
            self::assertStringNotContainsString('wikis', $sql['query']);
        }
        $result = $output->toArray();
        if ($result === []) {
            self::fail('Expected playlist details.');
        }
        self::assertSame(50, $result['videoCount']);
        self::assertCount(50, $result['items']);
        self::assertSame(range(0, 49), array_column($result['items'], 'position'));
        self::assertSame([
            'sourceVideoLinkIdentifier' => (string) $source,
            'provider' => 'youtube',
            'externalVideoId' => '00000000000',
            'position' => 0,
            'url' => 'https://www.youtube.com/watch?v=00000000000',
            'title' => 'Snapshot title',
            'thumbnailUrl' => 'https://i.ytimg.com/vi/00000000000/hqdefault.jpg',
            'durationSeconds' => 120,
        ], $result['items'][0]);
        DB::table('wiki_playlist_items')->where('playlist_id', (string) $id)->where('provider', 'youtube')->where('external_video_id', (string) $items[49]->externalVideoId())->update(['thumbnail_url' => null, 'duration_seconds' => null]);
        $query->process(new GetMyPlaylistInput($owner, $id), $output);
        $nullableResult = $output->toArray();
        if ($nullableResult === []) {
            self::fail('Expected playlist details.');
        }
        self::assertNull($nullableResult['items'][0]['thumbnailUrl']);
        self::assertNull($nullableResult['items'][0]['durationSeconds']);
    }

    public function testRetainsSnapshotsAfterUnlikingUnpublishingAndDeletingSource(): void
    {
        $this->seed([SystemPolicySeeder::class, SystemRoleSeeder::class]);
        $owner = $this->principal();
        $wiki = StrTestHelper::generateUuid();
        $source = new VideoLinkIdentifier(StrTestHelper::generateUuid());
        CreateWiki::create($wiki, 'talent', ['published_at' => '2026-01-01 00:00:00']);
        CreateVideoLink::create((string) $source, ['wiki_id' => $wiki]);
        DB::table('wiki_video_link_likes')->insert(['id' => StrTestHelper::generateUuid(), 'video_link_id' => (string) $source, 'principal_id' => (string) $owner, 'created_at' => '2026-01-01 00:00:00']);
        $id = new PlaylistIdentifier(StrTestHelper::generateUuid());
        $this->app()->make(PlaylistRepositoryInterface::class)->save(PlaylistTestHelper::playlist($id, $owner, [PlaylistTestHelper::item($id, 0, $source)]));
        $query = $this->app()->make(GetMyPlaylistInterface::class);
        $output = new GetMyPlaylistOutput();
        $input = new GetMyPlaylistInput($owner, $id);
        $query->process($input, $output);
        $before = $output->toArray();
        DB::table('wiki_video_link_likes')->where('video_link_id', (string) $source)->delete();
        $query->process($input, $output);
        self::assertSame($before, $output->toArray());
        DB::table('wikis')->where('id', $wiki)->update(['published_at' => null]);
        $query->process($input, $output);
        self::assertSame($before, $output->toArray());
        DB::table('wiki_video_links')->where('id', (string) $source)->delete();
        $query->process($input, $output);
        $before['items'][0]['sourceVideoLinkIdentifier'] = null;
        self::assertSame($before, $output->toArray());
    }

    public function testOtherOwnersAndMissingIdsHaveIdenticalNotFoundOutcome(): void
    {
        $this->seed([SystemPolicySeeder::class, SystemRoleSeeder::class]);
        $owner = $this->principal();
        $other = $this->principal();
        $playlist = PlaylistTestHelper::playlist(owner: $other);
        $this->app()->make(PlaylistRepositoryInterface::class)->save($playlist);
        foreach ([$playlist->playlistIdentifier(), new PlaylistIdentifier(StrTestHelper::generateUuid())] as $id) {
            $output = new GetMyPlaylistOutput();

            try {
                $this->app()->make(GetMyPlaylistInterface::class)->process(new GetMyPlaylistInput($owner, $id), $output);
                self::fail('Not found was expected.');
            } catch (PlaylistNotFoundException $e) {
                self::assertSame('Playlist not found.', $e->getMessage());
                self::assertSame([], $output->toArray());
            }
        }
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
        $output = new GetMyPlaylistOutput();
        $query = $this->app()->make(GetMyPlaylistInterface::class);
        if (! $allowed) {
            try {
                $query->process(new GetMyPlaylistInput($owner, $playlist->playlistIdentifier()), $output);
                self::fail('Unauthorized read was accepted.');
            } catch (DisallowedException) {
                self::assertSame([], $output->toArray());
            }

            return;
        }
        $query->process(new GetMyPlaylistInput($owner, $playlist->playlistIdentifier()), $output);
        $result = $output->toArray();
        if ($result === []) {
            self::fail('Expected playlist details.');
        }
        self::assertSame((string) $playlist->playlistIdentifier(), $result['playlistIdentifier']);
    }

    public function testMissingPrincipalIsRejected(): void
    {
        $this->expectException(PrincipalNotFoundException::class);
        $this->app()->make(GetMyPlaylistInterface::class)->process(
            new GetMyPlaylistInput(new PrincipalIdentifier(StrTestHelper::generateUuid()), new PlaylistIdentifier(StrTestHelper::generateUuid())),
            new GetMyPlaylistOutput(),
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
        DB::table('wiki_principal_group_role_attachments')->insert(['principal_group_id' => (string) $group, 'role_id' => DB::table('wiki_roles')->where('name', $role)->value('id')]);

        return $principal;
    }
}
