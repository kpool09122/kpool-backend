<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Infrastructure\Service;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Image\Domain\Repository\ImageRepositoryInterface;
use Source\Wiki\Principal\Infrastructure\Service\IdentityWithdrawalService;
use Source\Wiki\Shared\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\CreateIdentity;
use Tests\Helper\CreateImage;
use Tests\Helper\CreatePrincipal;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class IdentityWithdrawalServiceTest extends TestCase
{
    #[Group('useDb')]
    public function testArchivesEveryPrincipalAndDeletesDependentsWithoutTouchingOtherIdentities(): void
    {
        $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
        $otherIdentity = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identity);
        CreateIdentity::create($otherIdentity, ['email' => 'other@example.com']);
        $archivedAt = new DateTimeImmutable('2026-10-01 12:00:00');
        DB::table('archived_identities')->insert(['identity_id' => (string) $identity, 'language' => 'ja', 'identity_created_at' => $archivedAt, 'archived_at' => $archivedAt]);
        $principalIds = [StrTestHelper::generateUuid(), StrTestHelper::generateUuid()];
        foreach ($principalIds as $id) {
            CreatePrincipal::create(new PrincipalIdentifier($id), $identity);
            DB::table('demotion_warnings')->insert(['id' => StrTestHelper::generateUuid(), 'principal_id' => $id, 'warning_count' => 1, 'last_warning_month' => '2026-09']);
        }
        $otherPrincipal = StrTestHelper::generateUuid();
        CreatePrincipal::create(new PrincipalIdentifier($otherPrincipal), $otherIdentity);
        $imageId = StrTestHelper::generateUuid();
        CreateImage::create($imageId, ['uploader_id' => $principalIds[0], 'approver_id' => $principalIds[0]]);
        $historyId = StrTestHelper::generateUuid();
        DB::table('wiki_histories')->insert(['id' => $historyId, 'action_type' => 'publish', 'actor_id' => $principalIds[0], 'subject_name' => 'Shared public content', 'recorded_at' => $archivedAt]);
        DB::table('contribution_point_histories')->insert(['id' => StrTestHelper::generateUuid(), 'principal_id' => $principalIds[0], 'year_month' => '2026-09', 'points' => 1, 'resource_type' => 'talent', 'wiki_id' => StrTestHelper::generateUuid(), 'contributor_type' => 'editor', 'is_new_creation' => true]);
        DB::table('promotion_histories')->insert(['id' => StrTestHelper::generateUuid(), 'principal_id' => $principalIds[0], 'from_role' => 'editor', 'to_role' => 'approver', 'processed_at' => $archivedAt]);
        $original = DB::table('wiki_principals')->where('identity_id', (string) $identity)->get();

        $this->app()->make(IdentityWithdrawalService::class)->withdraw($identity, $archivedAt);

        $this->assertDatabaseMissing('wiki_principals', ['identity_id' => (string) $identity]);
        $this->assertDatabaseHas('wiki_principals', ['id' => $otherPrincipal]);
        foreach ($original as $principal) {
            $this->assertDatabaseHas('archived_principals', ['identity_id' => (string) $identity, 'principal_type' => 'wiki', 'principal_id' => $principal->id, 'account_id' => $principal->account_id, 'archived_at' => '2026-10-01 12:00:00']);
            $this->assertDatabaseMissing('demotion_warnings', ['principal_id' => $principal->id]);
        }
        $this->assertSame(2, DB::table('archived_principals')->where('identity_id', (string) $identity)->count());
        $image = $this->app()->make(ImageRepositoryInterface::class)->findById(new ImageIdentifier($imageId));
        $this->assertNotNull($image);
        $this->assertSame($principalIds[0], (string) $image->uploaderIdentifier());
        $this->assertDatabaseHas('wiki_histories', ['id' => $historyId, 'actor_id' => $principalIds[0], 'subject_name' => 'Shared public content']);
        $this->assertDatabaseMissing('contribution_point_histories', ['principal_id' => $principalIds[0]]);
        $this->assertDatabaseMissing('promotion_histories', ['principal_id' => $principalIds[0]]);
    }

    #[Group('useDb')]
    public function testUnregisteredIdentityProducesNoWikiArchive(): void
    {
        $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identity);
        $this->app()->make(IdentityWithdrawalService::class)->withdraw($identity, new DateTimeImmutable());
        $this->assertDatabaseMissing('archived_principals', ['identity_id' => (string) $identity]);
    }
}
