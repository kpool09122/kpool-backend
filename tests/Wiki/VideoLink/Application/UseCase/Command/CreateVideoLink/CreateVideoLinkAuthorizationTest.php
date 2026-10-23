<?php

declare(strict_types=1);

namespace Tests\Wiki\VideoLink\Application\UseCase\Command\CreateVideoLink;

use Database\Seeders\SystemPolicySeeder;
use Database\Seeders\SystemRoleSeeder;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\VideoLink\Application\UseCase\Command\CreateVideoLink\CreateVideoLinkInput;
use Source\Wiki\VideoLink\Application\UseCase\Command\CreateVideoLink\CreateVideoLinkInterface;
use Source\Wiki\VideoLink\Domain\Repository\VideoLinkRepositoryInterface;
use Source\Wiki\VideoLink\Domain\ValueObject\DisplayOrder;
use Source\Wiki\VideoLink\Domain\ValueObject\DurationSeconds;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoTitle;
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
class CreateVideoLinkAuthorizationTest extends TestCase
{
    /** @return iterable<string, array{string, bool}> */
    public static function roles(): iterable
    {
        foreach (['Collaborator', 'None', 'Administrator'] as $role) {
            yield $role => [$role, $role === 'Collaborator'];
        }
    }

    #[DataProvider('roles')]
    public function testSeededAuthorizationAndPreservation(string $role, bool $allowed): void
    {
        $this->seed([SystemPolicySeeder::class, SystemRoleSeeder::class]);
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
        foreach ([VideoUsage::MUSIC_VIDEO, VideoUsage::YOUTUBE_AUTO_VIEW_COUNT] as $usage) {
            CreateVideoLink::create(StrTestHelper::generateUuid(), ['wiki_id' => (string) $wikiId, 'resource_type' => ResourceType::TALENT->value, 'url' => 'https://example.com/' . $usage->value, 'video_usage' => $usage->value]);
        }
        $before = DB::table('wiki_video_links')->orderBy('id')->get()->toArray();
        $input = new CreateVideoLinkInput($principalId, ResourceType::TALENT, $wikiId, new ExternalContentLink('https://example.com/new'), VideoUsage::MUSIC_VIDEO, new VideoTitle('New video'), new DisplayOrder(1), new DurationSeconds(253));
        if (! $allowed) {
            try {
                $this->app()->make(CreateVideoLinkInterface::class)->process($input);
                self::fail('An unauthorized principal created a video.');
            } catch (DisallowedException) {
                self::assertEquals($before, DB::table('wiki_video_links')->orderBy('id')->get()->toArray());
            }

            return;
        }
        $this->app()->make(CreateVideoLinkInterface::class)->process($input);
        self::assertSame(3, DB::table('wiki_video_links')->count());
        /** @var object{id: string} $row */
        foreach ($before as $row) {
            self::assertEquals($row, DB::table('wiki_video_links')->where('id', $row->id)->first());
        }
        $videos = $this->app()->make(VideoLinkRepositoryInterface::class)->findByResourceAndUrls(ResourceType::TALENT, $wikiId, ['https://example.com/new']);
        self::assertCount(1, $videos);
        self::assertSame((string) $principalId, (string) $videos[0]->creatorPrincipalIdentifier());
        self::assertSame(253, $videos[0]->durationSeconds()?->value());
    }
}
