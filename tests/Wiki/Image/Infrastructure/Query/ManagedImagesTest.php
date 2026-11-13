<?php

declare(strict_types=1);

namespace Tests\Wiki\Image\Infrastructure\Query;

use Database\Seeders\SystemPolicySeeder;
use Database\Seeders\SystemRoleSeeder;
use Illuminate\Support\Facades\DB;
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
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\CreateAccount;
use Tests\Helper\CreateDraftImage;
use Tests\Helper\CreateIdentity;
use Tests\Helper\CreateImage;
use Tests\Helper\CreatePrincipal;
use Tests\Helper\CreatePrincipalGroup;
use Tests\Helper\CreatePrincipalGroupMembership;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class ManagedImagesTest extends TestCase
{
    public function testListsPublishedImagesAcrossWikisWithPaginationAndFiltersDrafts(): void
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
        DB::table('wiki_principal_group_role_attachments')->insert(['principal_group_id' => (string) $groupId, 'role_id' => DB::table('wiki_roles')->where('name', 'Operator')->value('id')]);
        $firstId = StrTestHelper::generateUuid();
        $secondId = StrTestHelper::generateUuid();
        $draftId = StrTestHelper::generateUuid();
        CreateImage::create($firstId, ['uploaded_at' => '2026-01-01 00:00:00']);
        CreateImage::create($secondId, ['uploaded_at' => '2026-01-02 00:00:00', 'is_hidden' => true]);
        CreateDraftImage::create($draftId, ['status' => ApprovalStatus::UnderReview->value]);
        $query = $this->app()->make(ListManagedImagesInterface::class);
        $output = new ListManagedImagesOutput();
        $query->process(new ListManagedImagesInput($principalId, ApprovalStatus::Approved, 1), $output);
        $payload = $output->toArray();
        self::assertSame(2, $payload['total']);
        self::assertSame(2, $payload['last_page']);
        self::assertCount(1, $payload['images']);
        self::assertSame($secondId, $payload['images'][0]['imageIdentifier']);
        self::assertTrue($payload['images'][0]['isHidden']);
        self::assertNull($payload['images'][0]['author']);
        self::assertSame('approved', $payload['images'][0]['status']);
        $query->process(new ListManagedImagesInput($principalId, ApprovalStatus::UnderReview), $output);
        self::assertSame([$draftId], array_column($output->toArray()['images'], 'imageIdentifier'));
    }

    public function testEditsMetadataImmediatelyPreservingApprovalFileSourceAndVisibility(): void
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
        DB::table('wiki_principal_group_role_attachments')->insert(['principal_group_id' => (string) $groupId, 'role_id' => DB::table('wiki_roles')->where('name', 'Operator')->value('id')]);
        $imageId = StrTestHelper::generateUuid();
        CreateImage::create($imageId, ['is_hidden' => true]);
        $before = DB::table('wiki_images')->where('id', $imageId)->first();
        $output = new UpdateImageMetadataOutput();
        $this->app()->make(UpdateImageMetadataInterface::class)->process(new UpdateImageMetadataInput(
            new ImageIdentifier($imageId),
            $principalId,
            'Updated source',
            new ImageAuthor('Creator'),
            ImageLicense::CC_BY,
            '',
            3,
        ), $output);
        $after = DB::table('wiki_images')->where('id', $imageId)->first();
        self::assertNotNull($before);
        self::assertNotNull($after);
        self::assertSame('Updated source', $after->source_name);
        self::assertSame('Creator', $after->author);
        self::assertSame('CC-BY-4.0', $after->license);
        self::assertSame('', $after->alt_text);
        self::assertSame(3, $after->display_order);
        foreach (['image_path', 'source_url', 'approved_at', 'approver_id', 'uploader_id', 'uploaded_at', 'is_hidden'] as $field) {
            self::assertSame($before->{$field}, $after->{$field});
        }
        self::assertSame((string) $principalId, $after->updater_id);
        self::assertNotNull($after->updated_at);
        self::assertSame(0, DB::table('draft_wiki_images')->count());
        self::assertSame(1, DB::table('wiki_image_snapshots')->count());
    }
}
