<?php

declare(strict_types=1);

namespace Tests\Wiki\VideoLink\Application\UseCase\Command\SendVideoWatchRequest;

use Database\Seeders\SystemPolicySeeder;
use Database\Seeders\SystemRoleSeeder;
use DateTimeImmutable;
use Exception;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\VideoLinkIdentifier;
use Source\Wiki\VideoLink\Application\Exception\VideoLinkNotFoundException;
use Source\Wiki\VideoLink\Application\Exception\VideoWatchRequestActiveException;
use Source\Wiki\VideoLink\Application\Exception\VideoWatchRequestDurationNotEligibleException;
use Source\Wiki\VideoLink\Application\Exception\VideoWatchRequestLikeRequiredException;
use Source\Wiki\VideoLink\Application\Exception\VideoWatchRequestResendNotAvailableException;
use Source\Wiki\VideoLink\Application\Exception\VideoWatchRequestTargetNotEligibleException;
use Source\Wiki\VideoLink\Application\Exception\VideoWatchRequestWeeklyLimitReachedException;
use Source\Wiki\VideoLink\Application\UseCase\Command\DeleteVideoLink\DeleteVideoLinkInput;
use Source\Wiki\VideoLink\Application\UseCase\Command\DeleteVideoLink\DeleteVideoLinkInterface;
use Source\Wiki\VideoLink\Application\UseCase\Command\SendVideoWatchRequest\SendVideoWatchRequestInput;
use Source\Wiki\VideoLink\Application\UseCase\Command\SendVideoWatchRequest\SendVideoWatchRequestInterface;
use Source\Wiki\VideoLink\Application\UseCase\Command\UnlikeVideoLink\UnlikeVideoLinkInput;
use Source\Wiki\VideoLink\Application\UseCase\Command\UnlikeVideoLink\UnlikeVideoLinkInterface;
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
class SendVideoWatchRequestTest extends TestCase
{
    public function testCollaboratorSendsRequestWithServerOwnedIdentityWikiAndTime(): void
    {
        $this->seed([SystemPolicySeeder::class, SystemRoleSeeder::class]);
        $principal = $this->principal();
        $wiki = StrTestHelper::generateUuid();
        CreateWiki::create($wiki, 'talent', ['owner_account_id' => StrTestHelper::generateUuid(), 'published_at' => '2026-01-01']);
        $video = new VideoLinkIdentifier(StrTestHelper::generateUuid());
        CreateVideoLink::create((string) $video, ['wiki_id' => $wiki, 'duration_seconds' => 30]);
        DB::table('wiki_video_link_likes')->insert(['id' => StrTestHelper::generateUuid(), 'video_link_id' => (string) $video, 'principal_id' => (string) $principal, 'created_at' => now()]);
        $before = new DateTimeImmutable();

        $this->app()->make(SendVideoWatchRequestInterface::class)->process(new SendVideoWatchRequestInput($video, $principal));
        $this->assertDatabaseHas('wiki_video_watch_requests', ['video_link_id' => (string) $video, 'principal_id' => (string) $principal, 'wiki_id' => $wiki]);
        $sentAt = DB::table('wiki_video_watch_requests')->where('video_link_id', (string) $video)->value('sent_at');
        self::assertIsString($sentAt);
        $sentAt = new DateTimeImmutable($sentAt);
        self::assertGreaterThanOrEqual($before->getTimestamp(), $sentAt->getTimestamp());
        self::assertLessThanOrEqual((new DateTimeImmutable())->getTimestamp(), $sentAt->getTimestamp());

    }

    public function testActiveRequestsPreventOwnUnlikeAndDeletionAndExpiredRequestsAllowThem(): void
    {
        $this->seed([SystemPolicySeeder::class, SystemRoleSeeder::class]);
        $principal = $this->principal();
        $other = $this->principal();
        $wiki = StrTestHelper::generateUuid();
        CreateWiki::create($wiki, 'talent', ['owner_account_id' => StrTestHelper::generateUuid(), 'published_at' => '2026-01-01']);
        $video = new VideoLinkIdentifier(StrTestHelper::generateUuid());
        CreateVideoLink::create((string) $video, ['wiki_id' => $wiki, 'creator_principal_id' => (string) $principal]);
        foreach ([$principal, $other] as $sender) {
            DB::table('wiki_video_link_likes')->insert(['id' => StrTestHelper::generateUuid(), 'video_link_id' => (string) $video, 'principal_id' => (string) $sender, 'created_at' => now()]);
        }
        DB::table('wiki_video_watch_requests')->insert(['id' => StrTestHelper::generateUuid(), 'video_link_id' => (string) $video, 'wiki_id' => $wiki, 'principal_id' => (string) $principal, 'sent_at' => new DateTimeImmutable('-167 hours')]);

        $unlike = $this->app()->make(UnlikeVideoLinkInterface::class);
        $delete = $this->app()->make(DeleteVideoLinkInterface::class);
        $unlike->process(new UnlikeVideoLinkInput($video, $other));
        foreach (['unlike', 'delete'] as $operation) {
            try {
                if ($operation === 'unlike') {
                    $unlike->process(new UnlikeVideoLinkInput($video, $principal));
                } else {
                    $delete->process(new DeleteVideoLinkInput($video, $principal));
                }
                self::fail('An active request must prevent ' . $operation);
            } catch (VideoWatchRequestActiveException) {
                $this->assertDatabaseHas('wiki_video_links', ['id' => (string) $video]);
                $this->assertDatabaseHas('wiki_video_link_likes', ['principal_id' => (string) $principal]);
            }
        }
        DB::table('wiki_video_watch_requests')->where('video_link_id', (string) $video)->update(['sent_at' => new DateTimeImmutable('-168 hours')]);
        $unlike->process(new UnlikeVideoLinkInput($video, $principal));
        $delete->process(new DeleteVideoLinkInput($video, $principal));
        $this->assertDatabaseMissing('wiki_video_links', ['id' => (string) $video]);
        self::assertSame(1, DB::table('wiki_video_watch_requests')->count());

    }

    public function testOtherSendersActiveRequestKeepsDeletionLockedAfterOwnUnlikeExpires(): void
    {
        $this->seed([SystemPolicySeeder::class, SystemRoleSeeder::class]);
        $principal = $this->principal();
        $other = $this->principal();
        $wiki = StrTestHelper::generateUuid();
        CreateWiki::create($wiki, 'talent');
        $video = new VideoLinkIdentifier(StrTestHelper::generateUuid());
        CreateVideoLink::create((string) $video, ['wiki_id' => $wiki, 'creator_principal_id' => (string) $principal]);
        DB::table('wiki_video_link_likes')->insert(['id' => StrTestHelper::generateUuid(), 'video_link_id' => (string) $video, 'principal_id' => (string) $principal, 'created_at' => now()]);
        foreach ([[$principal,new DateTimeImmutable('-168 hours')],[$other,new DateTimeImmutable('-167 hours')]] as [$sender,$time]) {
            DB::table('wiki_video_watch_requests')->insert(['id' => StrTestHelper::generateUuid(), 'video_link_id' => (string) $video, 'wiki_id' => $wiki, 'principal_id' => (string) $sender, 'sent_at' => $time]);
        }

        $this->app()->make(UnlikeVideoLinkInterface::class)->process(new UnlikeVideoLinkInput($video, $principal));
        $this->assertDatabaseMissing('wiki_video_link_likes', ['video_link_id' => (string) $video, 'principal_id' => (string) $principal]);
        $delete = $this->app()->make(DeleteVideoLinkInterface::class);

        try {
            $delete->process(new DeleteVideoLinkInput($video, $principal));
            self::fail('Another sender must still prevent deletion');
        } catch (VideoWatchRequestActiveException) {
            $this->assertDatabaseHas('wiki_video_links', ['id' => (string) $video]);
        }
        DB::table('wiki_video_watch_requests')->where('video_link_id', (string) $video)->where('principal_id', (string) $other)->update(['sent_at' => new DateTimeImmutable('-168 hours')]);
        $delete->process(new DeleteVideoLinkInput($video, $principal));
        $this->assertDatabaseMissing('wiki_video_links', ['id' => (string) $video]);
        self::assertSame(2, DB::table('wiki_video_watch_requests')->count());

    }

    /** @return list<array{string,class-string<Exception>|null}> */
    public static function rejectionCases(): array
    {
        return [
            ['missing-wiki',VideoWatchRequestTargetNotEligibleException::class], ['unofficial',DisallowedException::class], ['unpublished',null], ['wrong-wiki-type',DisallowedException::class], ['wrong-video-type',DisallowedException::class],
            ['auto-view',DisallowedException::class], ['auto-like',DisallowedException::class], ['auto-recent',DisallowedException::class],
            ['unliked',VideoWatchRequestLikeRequiredException::class], ['duration-31',VideoWatchRequestDurationNotEligibleException::class], ['duration-unknown',VideoWatchRequestDurationNotEligibleException::class],
            ['three',VideoWatchRequestWeeklyLimitReachedException::class], ['four',VideoWatchRequestWeeklyLimitReachedException::class], ['resend-before',VideoWatchRequestResendNotAvailableException::class],
            ['resend-exact',null], ['resend-after',null], ['two',null], ['duration-29',null],
            ['duration-zero',null], ['tiktok-duration-zero',null], ['tiktok-duration-29',null], ['tiktok-duration-30',null],
            ['tiktok-duration-31',VideoWatchRequestDurationNotEligibleException::class], ['tiktok-duration-unknown',VideoWatchRequestDurationNotEligibleException::class],
            ['quota-before',VideoWatchRequestWeeklyLimitReachedException::class], ['quota-exact',null], ['quota-after',null], ['other-sender',null], ['other-wiki',null],
        ];
    }

    #[DataProvider('rejectionCases')]
    public function testValidatesTargetDurationLikeQuotaAndResend(string $scenario, ?string $exceptionClass): void
    {
        $this->seed([SystemPolicySeeder::class, SystemRoleSeeder::class]);
        $principal = $this->principal();
        $wiki = StrTestHelper::generateUuid();
        $type = $scenario === 'wrong-wiki-type' ? 'group' : 'talent';
        if ($scenario !== 'missing-wiki') {
            CreateWiki::create($wiki, $type, ['owner_account_id' => $scenario === 'unofficial' ? null : StrTestHelper::generateUuid(), 'published_at' => $scenario === 'unpublished' ? null : '2026-01-01']);
        }
        $usage = match ($scenario) {
            'auto-view' => 'youtube_auto_view_count', 'auto-like' => 'youtube_auto_like_count', 'auto-recent' => 'youtube_auto_recent_popular', default => 'music_video'
        };
        $video = new VideoLinkIdentifier(StrTestHelper::generateUuid());
        $duration = match ($scenario) {
            'duration-31', 'tiktok-duration-31' => 31,
            'duration-unknown', 'tiktok-duration-unknown' => null,
            'duration-29', 'tiktok-duration-29' => 29,
            'duration-zero', 'tiktok-duration-zero' => 0,
            default => 30,
        };
        $overrides = ['wiki_id' => $wiki, 'resource_type' => $scenario === 'wrong-video-type' ? 'group' : 'talent', 'duration_seconds' => $duration, 'video_usage' => $usage];
        if (str_starts_with($scenario, 'tiktok-')) {
            $overrides['url'] = 'https://www.tiktok.com/@artist/video/1234567890123456789';
        }
        CreateVideoLink::create((string) $video, $overrides);
        if ($scenario !== 'unliked') {
            DB::table('wiki_video_link_likes')->insert(['id' => StrTestHelper::generateUuid(), 'video_link_id' => (string) $video, 'principal_id' => (string) $principal, 'created_at' => now()]);
        }
        $count = match ($scenario) {
            'two' => 2, 'three' => 3, 'four' => 4, 'quota-before', 'quota-exact', 'quota-after', 'other-sender', 'other-wiki' => 3, default => 0
        };
        for ($i = 0; $i < $count; ++$i) {
            DB::table('wiki_video_watch_requests')->insert(['id' => StrTestHelper::generateUuid(), 'video_link_id' => StrTestHelper::generateUuid(), 'wiki_id' => $scenario === 'other-wiki' ? StrTestHelper::generateUuid() : $wiki, 'principal_id' => $scenario === 'other-sender' ? StrTestHelper::generateUuid() : (string) $principal, 'sent_at' => match ($scenario) {
                'quota-before' => new DateTimeImmutable('-167 hours'), 'quota-exact' => new DateTimeImmutable('-168 hours'), 'quota-after' => new DateTimeImmutable('-169 hours'), default => new DateTimeImmutable('-144 hours')
            }]);
        }
        if (str_starts_with($scenario, 'resend-')) {
            $time = match ($scenario) {
                'resend-before' => new DateTimeImmutable('-167 hours'), 'resend-exact' => new DateTimeImmutable('-168 hours'), default => new DateTimeImmutable('-169 hours')
            };
            DB::table('wiki_video_watch_requests')->insert(['id' => StrTestHelper::generateUuid(), 'video_link_id' => (string) $video, 'wiki_id' => $wiki, 'principal_id' => (string) $principal, 'sent_at' => $time]);
        }
        $before = DB::table('wiki_video_watch_requests')->count();

        try {
            $this->app()->make(SendVideoWatchRequestInterface::class)->process(new SendVideoWatchRequestInput($video, $principal));
            self::assertNull($exceptionClass, 'An ineligible request was accepted');
            self::assertSame($before + 1, DB::table('wiki_video_watch_requests')->count());
        } catch (DisallowedException|VideoWatchRequestTargetNotEligibleException|VideoWatchRequestDurationNotEligibleException|VideoWatchRequestLikeRequiredException|VideoWatchRequestResendNotAvailableException|VideoWatchRequestWeeklyLimitReachedException $e) {
            self::assertNotNull($exceptionClass, 'An eligible request was rejected');
            self::assertSame($exceptionClass, $e::class);
            self::assertSame($before, DB::table('wiki_video_watch_requests')->count());
        }

    }

    /** @return list<array{string}> */
    public static function unauthorizedCases(): array
    {
        return [['None'], ['Administrator'], ['missing-policy'], ['deny'], ['suspended'], ['forced_stop'], ['missing-principal'], ['missing-video']];
    }

    #[DataProvider('unauthorizedCases')]
    public function testReauthorizesSenderAndDoesNotWriteOnFailure(string $scenario): void
    {
        $this->seed([SystemPolicySeeder::class, SystemRoleSeeder::class]);
        $principal = $this->principal(in_array($scenario, ['None','Administrator'], true) ? $scenario : 'Collaborator');
        $video = new VideoLinkIdentifier(StrTestHelper::generateUuid());
        if ($scenario !== 'missing-video') {
            $wiki = StrTestHelper::generateUuid();
            CreateWiki::create($wiki, 'talent', ['owner_account_id' => StrTestHelper::generateUuid()]);
            CreateVideoLink::create((string) $video, ['wiki_id' => $wiki]);
        }
        if ($scenario === 'missing-principal') {
            $principal = new PrincipalIdentifier(StrTestHelper::generateUuid());
        }
        if (in_array($scenario, ['suspended','forced_stop'], true)) {
            DB::table('wiki_principals')->where('id', (string) $principal)->update(['status' => $scenario]);
        }
        if ($scenario === 'missing-policy') {
            DB::table('wiki_role_policy_attachments')->where('policy_id', DB::table('wiki_policies')->where('name', 'GLOBAL_SEND_VIDEO_WATCH_REQUEST')->value('id'))->delete();
        }
        if ($scenario === 'deny') {
            DB::table('wiki_policies')->where('name', 'GLOBAL_SEND_VIDEO_WATCH_REQUEST')->update(['statements' => json_encode([['effect' => 'deny', 'actions' => ['send_video_watch_request'], 'resource_types' => ['talent'], 'condition' => null]])]);
        }

        try {
            $this->app()->make(SendVideoWatchRequestInterface::class)->process(new SendVideoWatchRequestInput($video, $principal));
            self::fail('Unauthorized send was accepted');
        } catch (DisallowedException|PrincipalNotFoundException|VideoLinkNotFoundException $e) {
            self::assertInstanceOf(match ($scenario) {
                'missing-video' => VideoLinkNotFoundException::class, 'missing-principal' => PrincipalNotFoundException::class, default => DisallowedException::class
            }, $e);
            self::assertSame(0, DB::table('wiki_video_watch_requests')->count());
        }
    }

    public function testTargetRestrictionsAreControlledByPolicyConditions(): void
    {
        $this->seed([SystemPolicySeeder::class, SystemRoleSeeder::class]);
        $principal = $this->principal();
        $wiki = StrTestHelper::generateUuid();
        CreateWiki::create($wiki, 'talent');
        $video = new VideoLinkIdentifier(StrTestHelper::generateUuid());
        CreateVideoLink::create((string) $video, ['wiki_id' => $wiki, 'video_usage' => 'youtube_auto_view_count', 'duration_seconds' => 30]);
        DB::table('wiki_video_link_likes')->insert(['id' => StrTestHelper::generateUuid(), 'video_link_id' => (string) $video, 'principal_id' => (string) $principal, 'created_at' => now()]);
        $query = $this->app()->make(SendVideoWatchRequestInterface::class);

        try {
            $query->process(new SendVideoWatchRequestInput($video, $principal));
            self::fail('The default policy must reject unofficial automatically collected videos');
        } catch (DisallowedException) {
            self::assertSame(0, DB::table('wiki_video_watch_requests')->count());
        }
        DB::table('wiki_policies')->where('name', 'GLOBAL_SEND_VIDEO_WATCH_REQUEST')->update([
            'statements' => json_encode([['effect' => 'allow', 'actions' => ['send_video_watch_request'], 'resource_types' => ['talent'], 'condition' => null]]),
        ]);

        $query->process(new SendVideoWatchRequestInput($video, $principal));

        $this->assertDatabaseHas('wiki_video_watch_requests', ['video_link_id' => (string) $video, 'principal_id' => (string) $principal]);
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
