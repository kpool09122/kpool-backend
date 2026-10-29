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
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Shared\Domain\ValueObject\VideoLinkIdentifier;
use Source\Wiki\VideoLink\Application\Exception\VideoAlreadyWatchedException;
use Source\Wiki\VideoLink\Application\UseCase\Command\RecordVideoLinkWatch\RecordVideoLinkWatchInput;
use Source\Wiki\VideoLink\Application\UseCase\Command\RecordVideoLinkWatch\RecordVideoLinkWatchInterface;
use Source\Wiki\VideoLink\Application\UseCase\Command\SendVideoWatchRequest\SendVideoWatchRequestInput;
use Source\Wiki\VideoLink\Application\UseCase\Command\SendVideoWatchRequest\SendVideoWatchRequestInterface;
use Source\Wiki\VideoLink\Application\UseCase\Query\ListMyVideoWatchRequests\ListMyVideoWatchRequestsInput;
use Source\Wiki\VideoLink\Application\UseCase\Query\ListMyVideoWatchRequests\ListMyVideoWatchRequestsInterface;
use Source\Wiki\VideoLink\Application\UseCase\Query\ListVideos\ListVideosInput;
use Source\Wiki\VideoLink\Application\UseCase\Query\ListVideos\ListVideosInterface;
use Source\Wiki\VideoLink\Application\UseCase\Query\ListVideos\ListVideosOutput;
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
class RecordVideoLinkWatchTest extends TestCase
{
    public function testCompletionIsIdempotentAndIndependentOfLikesAndBlocksResending(): void
    {
        $this->seed([SystemPolicySeeder::class, SystemRoleSeeder::class]);
        $account = new AccountIdentifier(StrTestHelper::generateUuid());
        CreateAccount::create((string) $account, ['category' => 'talent']);
        $principal = $this->principal($account);
        $wiki = StrTestHelper::generateUuid();
        CreateWiki::create($wiki, 'talent', ['owner_account_id' => (string) $account, 'slug' => 'tl-watched', 'language' => 'ja', 'published_at' => '2026-01-01']);
        $video = new VideoLinkIdentifier(StrTestHelper::generateUuid());
        CreateVideoLink::create((string) $video, ['wiki_id' => $wiki, 'duration_seconds' => 30]);
        $record = $this->app()->make(RecordVideoLinkWatchInterface::class);
        $record->process(new RecordVideoLinkWatchInput($video, $principal));
        $timestamp = DB::table('wiki_video_link_watches')->value('watched_at');
        $record->process(new RecordVideoLinkWatchInput($video, $this->principal($account)));
        self::assertSame(1, DB::table('wiki_video_link_watches')->count());
        self::assertSame($timestamp, DB::table('wiki_video_link_watches')->value('watched_at'));
        self::assertSame(0, DB::table('wiki_video_link_likes')->count());
        $this->assertDatabaseHas('wiki_video_link_watches', ['video_link_id' => (string) $video, 'owner_account_id' => (string) $account, 'principal_id' => (string) $principal]);

        $output = new ListVideosOutput();
        $this->app()->make(ListVideosInterface::class)->process(new ListVideosInput(new Slug('tl-watched'), Language::JAPANESE, ResourceType::TALENT), $output);
        self::assertTrue($output->toArray()['videos'][0]['hasOfficialOwnerWatch']);
        $state = $this->app()->make(ListMyVideoWatchRequestsInterface::class)->process(new ListMyVideoWatchRequestsInput([$video], $principal))[0];
        self::assertFalse($state->canSend);
        self::assertSame('already_watched', $state->reason);
        self::assertNull($state->nextSendAt);
        DB::table('wiki_video_link_likes')->insert(['id' => StrTestHelper::generateUuid(), 'video_link_id' => (string) $video, 'principal_id' => (string) $principal, 'created_at' => now()]);
        $this->expectException(VideoAlreadyWatchedException::class);
        $this->app()->make(SendVideoWatchRequestInterface::class)->process(new SendVideoWatchRequestInput($video, $principal));
    }

    /** @return array<string, array{string}> */
    public static function deniedViewers(): array
    {
        return ['other owner' => ['other'], 'delegated owner' => ['delegated'], 'inactive owner' => ['inactive'], 'unofficial wiki' => ['unofficial'], 'group wiki' => ['group'], 'mismatched wiki type' => ['wiki_group'], 'mismatched video type' => ['video_group'], 'missing permission' => ['no_policy'], 'explicit deny' => ['deny']];
    }

    #[DataProvider('deniedViewers')]
    public function testOnlyTheActiveNonDelegatedExactTalentOwnerCanRecord(string $kind): void
    {
        $this->seed([SystemPolicySeeder::class, SystemRoleSeeder::class]);
        $account = new AccountIdentifier(StrTestHelper::generateUuid());
        CreateAccount::create((string) $account, ['category' => 'talent']);
        $principal = $this->principal($account);
        if ($kind === 'delegated') {
            DB::table('wiki_principals')->where('id', (string) $principal)->update(['delegation_identifier' => StrTestHelper::generateUuid()]);
        }
        if ($kind === 'inactive') {
            DB::table('wiki_principals')->where('id', (string) $principal)->update(['status' => 'suspended']);
        }
        $wiki = StrTestHelper::generateUuid();
        $owner = $kind === 'unofficial' ? null : ($kind === 'other' ? StrTestHelper::generateUuid() : (string) $account);
        CreateWiki::create($wiki, in_array($kind, ['group', 'wiki_group'], true) ? 'group' : 'talent', ['owner_account_id' => $owner]);
        $video = new VideoLinkIdentifier(StrTestHelper::generateUuid());
        CreateVideoLink::create((string) $video, ['wiki_id' => $wiki, 'resource_type' => in_array($kind, ['group', 'video_group'], true) ? 'group' : 'talent']);
        if ($kind === 'no_policy') {
            DB::table('wiki_role_policy_attachments')->where('policy_id', DB::table('wiki_policies')->where('name', 'GLOBAL_RECORD_VIDEO_LINK_WATCH')->value('id'))->delete();
        }
        if ($kind === 'deny') {
            DB::table('wiki_policies')->where('name', 'GLOBAL_RECORD_VIDEO_LINK_WATCH')->update([
                'statements' => json_encode([['effect' => 'deny', 'actions' => ['record_video_link_watch'], 'resource_types' => ['talent'], 'condition' => null]]),
            ]);
        }
        $this->expectException(DisallowedException::class);

        try {
            $this->app()->make(RecordVideoLinkWatchInterface::class)->process(new RecordVideoLinkWatchInput($video, $principal));
        } finally {
            self::assertSame(0, DB::table('wiki_video_link_watches')->count());
        }
    }

    public function testAFormerOwnerCompletionDoesNotCountForTheNewOwner(): void
    {
        $this->seed([SystemPolicySeeder::class, SystemRoleSeeder::class]);
        $account = new AccountIdentifier(StrTestHelper::generateUuid());
        CreateAccount::create((string) $account, ['category' => 'talent']);
        $principal = $this->principal($account);
        $wiki = StrTestHelper::generateUuid();
        CreateWiki::create($wiki, 'talent', ['owner_account_id' => (string) $account, 'slug' => 'tl-watched', 'language' => 'ja', 'published_at' => '2026-01-01']);
        $video = new VideoLinkIdentifier(StrTestHelper::generateUuid());
        CreateVideoLink::create((string) $video, ['wiki_id' => $wiki]);
        $this->app()->make(RecordVideoLinkWatchInterface::class)->process(new RecordVideoLinkWatchInput($video, $principal));
        DB::table('wikis')->where('id', $wiki)->update(['owner_account_id' => StrTestHelper::generateUuid()]);
        $output = new ListVideosOutput();
        $this->app()->make(ListVideosInterface::class)->process(new ListVideosInput(new Slug('tl-watched'), Language::JAPANESE, ResourceType::TALENT), $output);
        self::assertFalse($output->toArray()['videos'][0]['hasOfficialOwnerWatch']);
    }

    private function principal(AccountIdentifier $account): PrincipalIdentifier
    {
        $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identity, ['email' => (string) $identity . '@example.com']);
        $principal = new PrincipalIdentifier(StrTestHelper::generateUuid());
        CreatePrincipal::create($principal, $identity, $account);
        // The seeded role grants the owner-scoped completion policy and request-state permissions.
        $role = DB::table('wiki_roles')->where('name', 'Collaborator')->value('id');
        if ($role !== null) {
            $group = new PrincipalGroupIdentifier(StrTestHelper::generateUuid());
            CreatePrincipalGroup::create($group, $account);
            CreatePrincipalGroupMembership::create((string) $group, (string) $principal);
            DB::table('wiki_principal_group_role_attachments')->insert(['principal_group_id' => (string) $group, 'role_id' => $role]);
        }

        return $principal;
    }
}
