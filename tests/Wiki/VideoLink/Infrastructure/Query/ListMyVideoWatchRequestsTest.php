<?php

declare(strict_types=1);

namespace Tests\Wiki\VideoLink\Infrastructure\Query;

use Carbon\CarbonImmutable;
use Database\Seeders\SystemPolicySeeder;
use Database\Seeders\SystemRoleSeeder;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\VideoLinkIdentifier;
use Source\Wiki\VideoLink\Application\Exception\VideoLinkNotFoundException;
use Source\Wiki\VideoLink\Application\UseCase\Query\ListMyVideoWatchRequests\ListMyVideoWatchRequestsInput;
use Source\Wiki\VideoLink\Application\UseCase\Query\ListMyVideoWatchRequests\ListMyVideoWatchRequestsInterface;
use Tests\Helper\CreateAccount;
use Tests\Helper\CreateIdentity;
use Tests\Helper\CreatePrincipal;
use Tests\Helper\CreatePrincipalGroup;
use Tests\Helper\CreatePrincipalGroupMembership;
use Tests\Helper\CreateVideoLink;
use Tests\Helper\CreateWiki;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class ListMyVideoWatchRequestsTest extends TestCase
{
    public function testPersonalStateUsesOwnRequestWhileDeletionUsesLatestSenderAndExpiresExactly(): void
    {
        $this->seed([SystemPolicySeeder::class, SystemRoleSeeder::class]);
        $principal = $this->principal();
        $other = $this->principal();
        $wiki = StrTestHelper::generateUuid();
        CreateWiki::create($wiki, 'talent', ['owner_account_id' => StrTestHelper::generateUuid(), 'published_at' => '2026-01-01']);
        $video = new VideoLinkIdentifier(StrTestHelper::generateUuid());
        CreateVideoLink::create((string) $video, ['wiki_id' => $wiki, 'duration_seconds' => 30]);
        DB::table('wiki_video_link_likes')->insert(['id' => StrTestHelper::generateUuid(), 'video_link_id' => (string) $video, 'principal_id' => (string) $principal, 'created_at' => now()]);
        foreach ([[(string) $principal,'2026-10-21 00:00:00'],[(string) $other,'2026-10-22 00:00:00']] as [$sender,$sent]) {
            DB::table('wiki_video_watch_requests')->insert(['id' => StrTestHelper::generateUuid(), 'video_link_id' => (string) $video, 'wiki_id' => $wiki, 'principal_id' => $sender, 'sent_at' => $sent]);
        }
        CarbonImmutable::setTestNow('2026-10-27 23:59:59 UTC');

        try {
            $query = $this->app()->make(ListMyVideoWatchRequestsInterface::class);
            $input = new ListMyVideoWatchRequestsInput([$video], $principal);
            $state = $query->process($input)[0]->toArray();
            self::assertTrue($state['sent']);
            self::assertFalse($state['canSend']);
            self::assertSame('resend_not_available', $state['reason']);
            self::assertSame('2026-10-28T00:00:00+00:00', $state['unlikeRestrictedUntil']);
            self::assertSame('2026-10-29T00:00:00+00:00', $state['deleteRestrictedUntil']);
            CarbonImmutable::setTestNow('2026-10-28 00:00:00 UTC');
            $state = $query->process($input)[0]->toArray();
            self::assertFalse($state['sent']);
            self::assertTrue($state['canSend']);
            self::assertNull($state['unlikeRestrictedUntil']);
            self::assertSame(3, $state['remainingRequests']);
            self::assertSame('2026-10-29T00:00:00+00:00', $state['deleteRestrictedUntil']);
            self::assertArrayNotHasKey('principalIdentifier', $state);
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function testQuotaIsScopedToCallerAndWikiAndNextTimeUsesLatestApplicableLimit(): void
    {
        $this->seed([SystemPolicySeeder::class, SystemRoleSeeder::class]);
        $principal = $this->principal();
        $other = $this->principal();
        $wiki = StrTestHelper::generateUuid();
        $otherWiki = StrTestHelper::generateUuid();
        CreateWiki::create($wiki, 'talent', ['owner_account_id' => StrTestHelper::generateUuid()]);
        $video = new VideoLinkIdentifier(StrTestHelper::generateUuid());
        CreateVideoLink::create((string) $video, ['wiki_id' => $wiki, 'duration_seconds' => 30]);
        foreach ([$principal, $other] as $sender) {
            DB::table('wiki_video_link_likes')->insert(['id' => StrTestHelper::generateUuid(), 'video_link_id' => (string) $video, 'principal_id' => (string) $sender, 'created_at' => now()]);
        }
        foreach ([['2026-10-22 00:00:00',(string) $video],['2026-10-23 00:00:00',StrTestHelper::generateUuid()],['2026-10-24 00:00:00',StrTestHelper::generateUuid()]] as [$time,$id]) {
            DB::table('wiki_video_watch_requests')->insert(['id' => StrTestHelper::generateUuid(), 'video_link_id' => $id, 'wiki_id' => $wiki, 'principal_id' => (string) $principal, 'sent_at' => $time]);
        }
        DB::table('wiki_video_watch_requests')->insert(['id' => StrTestHelper::generateUuid(), 'video_link_id' => StrTestHelper::generateUuid(), 'wiki_id' => $otherWiki, 'principal_id' => (string) $principal, 'sent_at' => '2026-10-27 00:00:00']);
        CarbonImmutable::setTestNow('2026-10-28 00:00:00 UTC');

        try {
            $query = $this->app()->make(ListMyVideoWatchRequestsInterface::class);
            $state = $query->process(new ListMyVideoWatchRequestsInput([$video], $principal))[0]->toArray();
            self::assertSame(0, $state['remainingRequests']);
            self::assertSame('2026-10-29T00:00:00+00:00', $state['nextSendAt']);
            $state = $query->process(new ListMyVideoWatchRequestsInput([$video], $other))[0]->toArray();
            self::assertSame(3, $state['remainingRequests']);
            self::assertTrue($state['canSend']);
            self::assertFalse($state['sent']);
            self::assertNull($state['lastSentAt']);
            self::assertNull($state['unlikeRestrictedUntil']);
            self::assertSame('2026-10-29T00:00:00+00:00', $state['deleteRestrictedUntil']);
            CarbonImmutable::setTestNow('2026-10-29 00:00:00 UTC');
            $state = $query->process(new ListMyVideoWatchRequestsInput([$video], $principal))[0]->toArray();
            self::assertSame(1, $state['remainingRequests']);
            self::assertTrue($state['canSend']);
            self::assertNull($state['nextSendAt']);
            self::assertSame('2026-10-22T00:00:00+00:00', $state['lastSentAt']);
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function testBatchKeepsVideoAndWikiStateSeparateAndDeduplicatesIds(): void
    {
        $this->seed([SystemPolicySeeder::class, SystemRoleSeeder::class]);
        $principal = $this->principal();
        $other = $this->principal();
        $wiki = StrTestHelper::generateUuid();
        $otherWiki = StrTestHelper::generateUuid();
        foreach ([$wiki, $otherWiki] as $wikiId) {
            CreateWiki::create($wikiId, 'talent', ['slug' => 'tl-watch-' . $wikiId, 'owner_account_id' => StrTestHelper::generateUuid()]);
        }
        $videos = [];
        foreach ([$wiki, $wiki, $otherWiki] as $wikiId) {
            $video = new VideoLinkIdentifier(StrTestHelper::generateUuid());
            CreateVideoLink::create((string) $video, ['wiki_id' => $wikiId, 'duration_seconds' => 30]);
            $videos[] = $video;
        }
        DB::table('wiki_video_link_likes')->insert(['id' => StrTestHelper::generateUuid(), 'video_link_id' => (string) $videos[0], 'principal_id' => (string) $principal, 'created_at' => now()]);
        foreach ([[$videos[0], $principal], [$videos[1], $other]] as [$video, $sender]) {
            DB::table('wiki_video_watch_requests')->insert(['id' => StrTestHelper::generateUuid(), 'video_link_id' => (string) $video, 'wiki_id' => $wiki, 'principal_id' => (string) $sender, 'sent_at' => '2026-10-27 00:00:00']);
        }
        CarbonImmutable::setTestNow('2026-10-28 00:00:00 UTC');

        try {
            $query = $this->app()->make(ListMyVideoWatchRequestsInterface::class);
            $states = $query->process(new ListMyVideoWatchRequestsInput([$videos[2], $videos[0], $videos[1], $videos[0]], $principal));
            self::assertCount(3, $states);
            $ids = array_column($states, 'videoLinkIdentifier');
            $sortedIds = $ids;
            sort($sortedIds);
            self::assertSame($sortedIds, $ids);
            $byId = array_column($states, null, 'videoLinkIdentifier');
            self::assertTrue($byId[(string) $videos[0]]->sent);
            self::assertSame(2, $byId[(string) $videos[0]]->remainingRequests);
            self::assertFalse($byId[(string) $videos[1]]->sent);
            self::assertSame('like_required', $byId[(string) $videos[1]]->reason);
            self::assertSame(2, $byId[(string) $videos[1]]->remainingRequests);
            self::assertNull($byId[(string) $videos[1]]->unlikeRestrictedUntil);
            self::assertSame('2026-11-03T00:00:00+00:00', $byId[(string) $videos[1]]->deleteRestrictedUntil);
            self::assertSame(3, $byId[(string) $videos[2]]->remainingRequests);
            self::assertNull($byId[(string) $videos[2]]->deleteRestrictedUntil);
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function testMissingVideoRejectsTheBatch(): void
    {
        $this->seed([SystemPolicySeeder::class, SystemRoleSeeder::class]);
        $principal = $this->principal();
        $video = new VideoLinkIdentifier(StrTestHelper::generateUuid());
        CreateVideoLink::create((string) $video);
        $query = $this->app()->make(ListMyVideoWatchRequestsInterface::class);
        $this->expectException(VideoLinkNotFoundException::class);
        $query->process(new ListMyVideoWatchRequestsInput([$video, new VideoLinkIdentifier(StrTestHelper::generateUuid())], $principal));
    }

    public function testUnauthorizedCallerCannotReadTheBatch(): void
    {
        $this->seed([SystemPolicySeeder::class, SystemRoleSeeder::class]);
        $principal = $this->principal('None');
        $video = new VideoLinkIdentifier(StrTestHelper::generateUuid());
        CreateVideoLink::create((string) $video);
        $query = $this->app()->make(ListMyVideoWatchRequestsInterface::class);
        $this->expectException(DisallowedException::class);
        $query->process(new ListMyVideoWatchRequestsInput([$video], $principal));
    }

    /** @return iterable<string, array{string, ?int, bool}> */
    public static function storedDurationCases(): iterable
    {
        foreach ([
            'youtube' => 'https://www.youtube.com/watch?v=abcdefghijk',
            'tiktok' => 'https://www.tiktok.com/@artist/video/1234567890123456789',
        ] as $provider => $url) {
            foreach ([null, 0, 29, 30, 31] as $duration) {
                yield $provider . '-' . ($duration ?? 'unknown') => [$url, $duration, $duration !== null && $duration <= 30];
            }
        }
    }

    #[DataProvider('storedDurationCases')]
    public function testCanSendUsesStoredDuration(string $url, ?int $duration, bool $canSend): void
    {
        $this->seed([SystemPolicySeeder::class, SystemRoleSeeder::class]);
        $principal = $this->principal();
        $wiki = StrTestHelper::generateUuid();
        CreateWiki::create($wiki, 'talent', ['owner_account_id' => StrTestHelper::generateUuid()]);
        $video = new VideoLinkIdentifier(StrTestHelper::generateUuid());
        CreateVideoLink::create((string) $video, ['wiki_id' => $wiki, 'url' => $url, 'duration_seconds' => $duration]);
        DB::table('wiki_video_link_likes')->insert(['id' => StrTestHelper::generateUuid(), 'video_link_id' => (string) $video, 'principal_id' => (string) $principal, 'created_at' => now()]);

        $query = $this->app()->make(ListMyVideoWatchRequestsInterface::class);
        $state = $query->process(new ListMyVideoWatchRequestsInput([$video], $principal))[0];

        self::assertSame($canSend, $state->canSend);
        self::assertSame($canSend ? null : 'duration_not_eligible', $state->reason);
    }

    public function testMixedTargetsReturnIndividualPolicyResultsWithoutRejectingTheBatch(): void
    {
        $this->seed([SystemPolicySeeder::class, SystemRoleSeeder::class]);
        $principal = $this->principal();
        $videos = [];
        foreach (['eligible', 'unofficial', 'automatic', 'wrong-wiki-type', 'missing-wiki'] as $scenario) {
            $wiki = StrTestHelper::generateUuid();
            if ($scenario !== 'missing-wiki') {
                CreateWiki::create($wiki, $scenario === 'wrong-wiki-type' ? 'group' : 'talent', [
                    'slug' => 'watch-' . $wiki,
                    'owner_account_id' => $scenario === 'unofficial' ? null : StrTestHelper::generateUuid(),
                ]);
            }
            $video = new VideoLinkIdentifier(StrTestHelper::generateUuid());
            CreateVideoLink::create((string) $video, ['wiki_id' => $wiki, 'duration_seconds' => 30, 'video_usage' => $scenario === 'automatic' ? 'youtube_auto_view_count' : 'music_video']);
            DB::table('wiki_video_link_likes')->insert(['id' => StrTestHelper::generateUuid(), 'video_link_id' => (string) $video, 'principal_id' => (string) $principal, 'created_at' => now()]);
            $videos[$scenario] = $video;
        }
        $query = $this->app()->make(ListMyVideoWatchRequestsInterface::class);
        $states = $query->process(new ListMyVideoWatchRequestsInput(array_values($videos), $principal));
        $byId = array_column($states, null, 'videoLinkIdentifier');
        self::assertCount(5, $byId);
        foreach ($videos as $scenario => $video) {
            self::assertSame($scenario === 'eligible', $byId[(string) $video]->canSend);
            self::assertSame($scenario === 'eligible' ? null : 'target_not_eligible', $byId[(string) $video]->reason);
        }
    }

    public function testSendPolicyDenialIsReturnedAsStateWhileReadPermissionIsRequired(): void
    {
        $this->seed([SystemPolicySeeder::class, SystemRoleSeeder::class]);
        $principal = $this->principal();
        $wiki = StrTestHelper::generateUuid();
        CreateWiki::create($wiki, 'talent', ['owner_account_id' => StrTestHelper::generateUuid()]);
        $video = new VideoLinkIdentifier(StrTestHelper::generateUuid());
        CreateVideoLink::create((string) $video, ['wiki_id' => $wiki, 'duration_seconds' => 30]);
        DB::table('wiki_video_link_likes')->insert(['id' => StrTestHelper::generateUuid(), 'video_link_id' => (string) $video, 'principal_id' => (string) $principal, 'created_at' => now()]);
        DB::table('wiki_policies')->where('name', 'GLOBAL_SEND_VIDEO_WATCH_REQUEST')->update([
            'statements' => json_encode([['effect' => 'deny', 'actions' => ['send_video_watch_request'], 'resource_types' => ['talent'], 'condition' => null]]),
        ]);
        $query = $this->app()->make(ListMyVideoWatchRequestsInterface::class);
        $state = $query->process(new ListMyVideoWatchRequestsInput([$video], $principal))[0];
        self::assertFalse($state->canSend);
        self::assertSame('target_not_eligible', $state->reason);

        DB::table('wiki_role_policy_attachments')->where('policy_id', DB::table('wiki_policies')->where('name', 'GLOBAL_LIST_MY_VIDEO_WATCH_REQUESTS')->value('id'))->delete();
        $this->expectException(DisallowedException::class);
        $query->process(new ListMyVideoWatchRequestsInput([$video], $principal));
    }

    private function principal(string $role = 'Collaborator'): PrincipalIdentifier
    {
        $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identity, ['email' => (string) $identity . '@example.com']);
        $principal = new PrincipalIdentifier(StrTestHelper::generateUuid());
        CreatePrincipal::create($principal, $identity);
        $group = new PrincipalGroupIdentifier(StrTestHelper::generateUuid());
        $account = new AccountIdentifier(StrTestHelper::generateUuid());
        CreateAccount::create((string) $account);
        CreatePrincipalGroup::create($group, $account);
        CreatePrincipalGroupMembership::create((string) $group, (string) $principal);
        DB::table('wiki_principal_group_role_attachments')->insert(['principal_group_id' => (string) $group, 'role_id' => DB::table('wiki_roles')->where('name', $role)->value('id')]);

        return $principal;
    }
}
