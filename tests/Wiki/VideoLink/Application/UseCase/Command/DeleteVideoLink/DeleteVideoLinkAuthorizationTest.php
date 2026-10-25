<?php

declare(strict_types=1);

namespace Tests\Wiki\VideoLink\Application\UseCase\Command\DeleteVideoLink;

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
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\VideoLinkIdentifier;
use Source\Wiki\VideoLink\Application\UseCase\Command\DeleteVideoLink\DeleteVideoLinkInput;
use Source\Wiki\VideoLink\Application\UseCase\Command\DeleteVideoLink\DeleteVideoLinkInterface;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoUsage;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
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
class DeleteVideoLinkAuthorizationTest extends TestCase
{
    /** @return iterable<string, array{string, bool, VideoUsage}> */
    public static function roles(): iterable
    {
        foreach (['Collaborator', 'None', 'Administrator'] as $role) {
            foreach (VideoUsage::cases() as $usage) {
                yield $role . ' ' . $usage->value => [$role, $role === 'Collaborator', $usage];
            }
        }
        foreach (VideoUsage::cases() as $usage) {
            yield 'Collaborator with creation permission only ' . $usage->value => ['Collaborator', false, $usage];
        }
    }

    #[DataProvider('roles')]
    public function testSeededAuthorizationAndPreservation(string $role, bool $allowed, VideoUsage $usage): void
    {
        $this->seed([SystemPolicySeeder::class, SystemRoleSeeder::class]);
        if ($role === 'Collaborator' && ! $allowed) {
            DB::table('wiki_role_policy_attachments')
                ->where('role_id', DB::table('wiki_roles')->where('name', $role)->value('id'))
                ->where('policy_id', DB::table('wiki_policies')->where('name', 'GLOBAL_DELETE_VIDEO_LINK')->value('id'))
                ->delete();
        }
        $identityId = new IdentityIdentifier(StrTestHelper::generateUuid());
        $accountId = new AccountIdentifier(StrTestHelper::generateUuid());
        $principalId = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $groupId = new PrincipalGroupIdentifier(StrTestHelper::generateUuid());
        $wikiId = new WikiIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identityId);
        CreateAccount::create((string) $accountId);
        CreatePrincipal::create($principalId, $identityId, $accountId);
        CreatePrincipalGroup::create($groupId, $accountId);
        CreatePrincipalGroupMembership::create((string) $groupId, (string) $principalId);
        DB::table('wiki_principal_group_role_attachments')->insert(['principal_group_id' => (string) $groupId, 'role_id' => DB::table('wiki_roles')->where('name', $role)->value('id')]);
        CreateWiki::create((string) $wikiId, ResourceType::TALENT->value);
        $videoLinkId = new VideoLinkIdentifier(StrTestHelper::generateUuid());
        CreateVideoLink::create((string) $videoLinkId, [
            'wiki_id' => (string) $wikiId,
            'resource_type' => ResourceType::TALENT->value,
            'video_usage' => $usage->value,
            'creator_principal_id' => $usage->isManual() ? (string) $principalId : null,
        ]);
        CreateVideoLink::create(StrTestHelper::generateUuid(), ['wiki_id' => (string) $wikiId]);
        $before = DB::table('wiki_video_links')->orderBy('id')->get()->toArray();
        $input = new DeleteVideoLinkInput($videoLinkId, $principalId);
        if (! $allowed) {
            try {
                $this->app()->make(DeleteVideoLinkInterface::class)->process($input);
                self::fail('An unauthorized principal deleted a video.');
            } catch (DisallowedException) {
                self::assertEquals($before, DB::table('wiki_video_links')->orderBy('id')->get()->toArray());
            }

            return;
        }
        $this->app()->make(DeleteVideoLinkInterface::class)->process($input);
        self::assertSame(1, DB::table('wiki_video_links')->count());
        self::assertNull(DB::table('wiki_video_links')->where('id', (string) $videoLinkId)->first());
        /** @var object{id: string} $row */
        foreach ($before as $row) {
            if ($row->id !== (string) $videoLinkId) {
                self::assertEquals($row, DB::table('wiki_video_links')->where('id', $row->id)->first());
            }
        }
    }
}
