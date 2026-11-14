<?php

declare(strict_types=1);

namespace Tests\Wiki\Image\Infrastructure\Query;

use Database\Seeders\SystemPolicySeeder;
use Database\Seeders\SystemRoleSeeder;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Image\Application\UseCase\Command\UpdateImageMetadata\UpdateImageMetadataInput;
use Source\Wiki\Image\Application\UseCase\Command\UpdateImageMetadata\UpdateImageMetadataInterface;
use Source\Wiki\Image\Application\UseCase\Command\UpdateImageMetadata\UpdateImageMetadataOutput;
use Source\Wiki\Image\Application\UseCase\Query\ListManagedImages\ListManagedImagesInput;
use Source\Wiki\Image\Application\UseCase\Query\ListManagedImages\ListManagedImagesInterface;
use Source\Wiki\Image\Application\UseCase\Query\ListManagedImages\ListManagedImagesOutput;
use Source\Wiki\Image\Domain\ValueObject\ImageAuthor;
use Source\Wiki\Image\Domain\ValueObject\ImageLicense;
use Source\Wiki\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\CreateAccount;
use Tests\Helper\CreateIdentity;
use Tests\Helper\CreateImage;
use Tests\Helper\CreatePrincipal;
use Tests\Helper\CreatePrincipalGroup;
use Tests\Helper\CreatePrincipalGroupMembership;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class ManagedImageAuthorizationTest extends TestCase
{
    /** @return iterable<string, array{string, bool}> */
    public static function roles(): iterable
    {
        foreach (['Operator', 'SeniorCollaborator', 'Administrator', 'AgencyActor', 'TalentActor', 'Collaborator', 'None'] as $role) {
            yield $role => [$role, $role === 'Operator'];
        }
    }

    #[DataProvider('roles')]
    public function testSeededRoleAuthorizationBeforeSearch(string $role, bool $allowed): void
    {
        $this->seed([SystemPolicySeeder::class, SystemRoleSeeder::class]);
        $identityId = new IdentityIdentifier(StrTestHelper::generateUuid());
        $accountId = new AccountIdentifier(StrTestHelper::generateUuid());
        $principalId = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $groupId = new PrincipalGroupIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identityId);
        CreateAccount::create((string) $accountId);
        CreatePrincipal::create($principalId, $identityId, $accountId);
        CreatePrincipalGroup::create($groupId, $accountId);
        CreatePrincipalGroupMembership::create((string) $groupId, (string) $principalId);
        DB::table('wiki_principal_group_role_attachments')->insert(['principal_group_id' => (string) $groupId, 'role_id' => DB::table('wiki_roles')->where('name', $role)->value('id')]);
        if (! $allowed) {
            $this->expectException(DisallowedException::class);
        }
        $output = new ListManagedImagesOutput();
        $this->app()->make(ListManagedImagesInterface::class)->process(new ListManagedImagesInput($principalId, ApprovalStatus::Approved), $output);
        self::assertSame(0, $output->toArray()['total']);
    }

    #[DataProvider('roles')]
    public function testOnlyOperatorCanUpdateMetadata(string $role, bool $allowed): void
    {
        $this->seed([SystemPolicySeeder::class, SystemRoleSeeder::class]);
        $identityId = new IdentityIdentifier(StrTestHelper::generateUuid());
        $accountId = new AccountIdentifier(StrTestHelper::generateUuid());
        $principalId = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $groupId = new PrincipalGroupIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identityId);
        CreateAccount::create((string) $accountId);
        CreatePrincipal::create($principalId, $identityId, $accountId);
        CreatePrincipalGroup::create($groupId, $accountId);
        CreatePrincipalGroupMembership::create((string) $groupId, (string) $principalId);
        DB::table('wiki_principal_group_role_attachments')->insert(['principal_group_id' => (string) $groupId, 'role_id' => DB::table('wiki_roles')->where('name', $role)->value('id')]);
        $imageId = StrTestHelper::generateUuid();
        CreateImage::create($imageId);
        if (! $allowed) {
            $this->expectException(DisallowedException::class);
        }
        $output = new UpdateImageMetadataOutput();
        $this->app()->make(UpdateImageMetadataInterface::class)->process(new UpdateImageMetadataInput(
            new ImageIdentifier($imageId),
            $principalId,
            'Source',
            new ImageAuthor('Creator'),
            ImageLicense::CC_BY,
            'Alt',
            0,
            sourceUrl: 'https://example.org/source',
        ), $output);
        self::assertSame('Creator', DB::table('wiki_images')->where('id', $imageId)->value('author'));
    }
}
