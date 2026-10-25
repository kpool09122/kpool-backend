<?php

declare(strict_types=1);

namespace Tests\Wiki\VideoLink\Application\UseCase\Command;

use Database\Seeders\SystemPolicySeeder;
use Database\Seeders\SystemRoleSeeder;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\VideoLink\Application\Exception\VideoLinkAlreadyLikedException;
use Source\Wiki\VideoLink\Application\Exception\VideoLinkNotFoundException;
use Source\Wiki\VideoLink\Application\UseCase\Command\LikeVideoLink\LikeVideoLinkInput;
use Source\Wiki\VideoLink\Application\UseCase\Command\LikeVideoLink\LikeVideoLinkInterface;
use Source\Wiki\VideoLink\Application\UseCase\Command\UnlikeVideoLink\UnlikeVideoLinkInput;
use Source\Wiki\VideoLink\Application\UseCase\Command\UnlikeVideoLink\UnlikeVideoLinkInterface;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoLinkIdentifier;
use Tests\Helper\CreateAccount;
use Tests\Helper\CreateIdentity;
use Tests\Helper\CreatePrincipal;
use Tests\Helper\CreatePrincipalGroup;
use Tests\Helper\CreatePrincipalGroupMembership;
use Tests\Helper\CreateVideoLink;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class VideoLinkLikeAuthorizationTest extends TestCase
{
    /** @return iterable<string, array{string,string,string}> */
    public static function allowedVideos(): iterable
    {
        foreach (['Collaborator', 'SeniorCollaborator', 'Operator', 'AgencyActor', 'TalentActor'] as $role) {
            foreach (['agency', 'group', 'song', 'talent'] as $type) {
                foreach (['music_video', 'youtube_auto_view_count', 'youtube_auto_like_count', 'youtube_auto_recent_popular'] as $usage) {
                    yield "$role/$type/$usage" => [$role, $type, $usage];
                }
            }
        }
    }

    #[DataProvider('allowedVideos')]
    public function testAuthorizedLikesRejectDuplicatesAndUnlikeOnlyDeletesOwnRecord(string $role, string $type, string $usage): void
    {
        $this->seed([SystemPolicySeeder::class, SystemRoleSeeder::class]);
        $principal = $this->principal($role);
        $other = $this->principal('Collaborator');
        $video = new VideoLinkIdentifier(StrTestHelper::generateUuid());
        CreateVideoLink::create((string) $video, ['resource_type' => $type, 'video_usage' => $usage]);
        $likeVideoLink = $this->app()->make(LikeVideoLinkInterface::class);
        $unlikeVideoLink = $this->app()->make(UnlikeVideoLinkInterface::class);
        $likeVideoLink->process(new LikeVideoLinkInput($video, $principal));
        $before = DB::table('wiki_video_link_likes')->where('principal_id', (string) $principal)->first();

        try {
            $likeVideoLink->process(new LikeVideoLinkInput($video, $principal));
            self::fail('An already liked video link was accepted.');
        } catch (VideoLinkAlreadyLikedException) {
            self::assertSame(1, DB::table('wiki_video_link_likes')->count());
        }
        self::assertEquals($before, DB::table('wiki_video_link_likes')->where('principal_id', (string) $principal)->first());
        $likeVideoLink->process(new LikeVideoLinkInput($video, $other));
        self::assertSame(2, DB::table('wiki_video_link_likes')->count());
        $unlikeVideoLink->process(new UnlikeVideoLinkInput($video, $principal));
        $unlikeVideoLink->process(new UnlikeVideoLinkInput($video, $principal));
        self::assertSame(1, DB::table('wiki_video_link_likes')->count());
        $this->assertDatabaseHas('wiki_video_link_likes', ['principal_id' => (string) $other]);
    }

    /** @return iterable<string, array{bool,string}> */
    public static function operations(): iterable
    {
        foreach ([false, true] as $unlike) {
            foreach (['None', 'Administrator', 'missing-policy', 'deny', 'missing-video', 'missing-principal'] as $scenario) {
                yield ($unlike ? 'unlike' : 'like') . '/' . $scenario => [$unlike, $scenario];
            }
        }
    }

    #[DataProvider('operations')]
    public function testRejectsMissingResourcesAndUnauthorizedPrincipalWithoutChangingLikes(bool $unlike, string $scenario): void
    {
        $this->seed([SystemPolicySeeder::class, SystemRoleSeeder::class]);
        $principal = $this->principal(in_array($scenario, ['None', 'Administrator'], true) ? $scenario : 'Collaborator');
        $video = new VideoLinkIdentifier(StrTestHelper::generateUuid());
        if ($scenario !== 'missing-video') {
            CreateVideoLink::create((string) $video);
            $this->app()->make(LikeVideoLinkInterface::class)->process(new LikeVideoLinkInput($video, $this->principal('Collaborator')));
        }
        $action = $unlike ? 'unlike_video_link' : 'like_video_link';
        if ($scenario === 'missing-policy') {
            DB::table('wiki_role_policy_attachments')->where('policy_id', DB::table('wiki_policies')->where('name', 'GLOBAL_' . strtoupper($action))->value('id'))->delete();
        }
        if ($scenario === 'deny') {
            $policyId = StrTestHelper::generateUuid();
            DB::table('wiki_policies')->insert(['id' => $policyId, 'name' => 'explicit deny', 'statements' => json_encode([['effect' => 'deny', 'actions' => [$action], 'resource_types' => ['talent'], 'condition' => null]]), 'created_at' => now(), 'updated_at' => now()]);
            DB::table('wiki_role_policy_attachments')->insert(['role_id' => DB::table('wiki_roles')->where('name', 'Collaborator')->value('id'), 'policy_id' => $policyId]);
        }
        if ($scenario === 'missing-principal') {
            $principal = new PrincipalIdentifier(StrTestHelper::generateUuid());
        }
        $before = DB::table('wiki_video_link_likes')->orderBy('id')->get()->toArray();

        try {
            if ($unlike) {
                $this->app()->make(UnlikeVideoLinkInterface::class)->process(new UnlikeVideoLinkInput($video, $principal));
            } else {
                $this->app()->make(LikeVideoLinkInterface::class)->process(new LikeVideoLinkInput($video, $principal));
            }
            self::fail('Unauthorized or missing resource operation succeeded');
        } catch (DisallowedException|VideoLinkNotFoundException|PrincipalNotFoundException $e) {
            self::assertInstanceOf(match ($scenario) {
                'missing-video' => VideoLinkNotFoundException::class,
                'missing-principal' => PrincipalNotFoundException::class,
                default => DisallowedException::class,
            }, $e);
            self::assertEquals($before, DB::table('wiki_video_link_likes')->orderBy('id')->get()->toArray());
        }
    }

    /** @return iterable<string, array{bool,string}> */
    public static function resourceScopes(): iterable
    {
        foreach ([false, true] as $unlike) {
            foreach (['agency', 'group', 'song', 'talent'] as $type) {
                yield ($unlike ? 'unlike/' : 'like/') . $type => [$unlike, $type];
            }
        }
    }

    #[DataProvider('resourceScopes')]
    public function testUsesTargetVideoResourceTypeAndReauthorizesExistingLikes(bool $unlike, string $type): void
    {
        $this->seed([SystemPolicySeeder::class, SystemRoleSeeder::class]);
        $principal = $this->principal('Collaborator');
        $video = new VideoLinkIdentifier(StrTestHelper::generateUuid());
        CreateVideoLink::create((string) $video, ['resource_type' => $type]);
        $this->app()->make(LikeVideoLinkInterface::class)->process(new LikeVideoLinkInput($video, $principal));
        $action = $unlike ? 'unlike_video_link' : 'like_video_link';
        $wrongType = $type === 'talent' ? 'agency' : 'talent';
        DB::table('wiki_policies')->where('name', 'GLOBAL_' . strtoupper($action))->update([
            'statements' => json_encode([['effect' => 'allow', 'actions' => [$action], 'resource_types' => [$wrongType], 'condition' => null]]),
        ]);
        $before = DB::table('wiki_video_link_likes')->get()->toArray();

        try {
            if ($unlike) {
                $this->app()->make(UnlikeVideoLinkInterface::class)->process(new UnlikeVideoLinkInput($video, $principal));
            } else {
                $this->app()->make(LikeVideoLinkInterface::class)->process(new LikeVideoLinkInput($video, $principal));
            }
            self::fail('A policy for a different resource type authorized an existing like.');
        } catch (DisallowedException) {
            self::assertEquals($before, DB::table('wiki_video_link_likes')->get()->toArray());
        }
    }

    private function principal(string $role): PrincipalIdentifier
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
