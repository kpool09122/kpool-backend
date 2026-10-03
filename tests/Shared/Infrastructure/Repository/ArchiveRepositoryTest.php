<?php

declare(strict_types=1);

namespace Tests\Shared\Infrastructure\Repository;

use Application\Models\Account\ArchivedAccount;
use Application\Models\Identity\ArchivedIdentity;
use Application\Models\Shared\ArchivedPrincipal;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Group;
use Source\Account\Account\Domain\Factory\ArchivedAccountFactoryInterface;
use Source\Account\Account\Domain\Repository\ArchivedAccountRepositoryInterface;
use Source\Account\Shared\Domain\ValueObject\AccountType;
use Source\Identity\Domain\Factory\ArchivedIdentityFactoryInterface;
use Source\Identity\Domain\Repository\ArchivedIdentityRepositoryInterface;
use Source\Shared\Application\Service\Uuid\UuidValidator;
use Source\Shared\Domain\Factory\ArchivedPrincipalFactoryInterface;
use Source\Shared\Domain\Repository\ArchivedPrincipalRepositoryInterface;
use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\ArchivedPrincipalType;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class ArchiveRepositoryTest extends TestCase
{
    public function testArchivesHaveIndependentIdsAndPreserveSourceReferences(): void
    {
        $identityId = new IdentityIdentifier(StrTestHelper::generateUuid());
        $accountId = new AccountIdentifier(StrTestHelper::generateUuid());
        $principalId = StrTestHelper::generateUuid();
        $beforeCreation = new DateTimeImmutable();
        $identity = $this->app()->make(ArchivedIdentityFactoryInterface::class)->create($identityId, Language::JAPANESE, null);
        $account = $this->app()->make(ArchivedAccountFactoryInterface::class)->create($accountId, AccountCategory::GENERAL, AccountType::INDIVIDUAL);

        $afterCreation = new DateTimeImmutable();
        foreach ([$identity->archivedAt(), $account->archivedAt()] as $archivedAt) {
            $this->assertGreaterThanOrEqual($beforeCreation, $archivedAt);
            $this->assertLessThanOrEqual($afterCreation, $archivedAt);
        }

        $this->app()->make(ArchivedIdentityRepositoryInterface::class)->save($identity);
        $this->app()->make(ArchivedAccountRepositoryInterface::class)->save($account);

        $identityArchiveId = (string) $identity->archivedIdentityIdentifier();
        $accountArchiveId = (string) $account->archivedAccountIdentifier();
        $this->assertTrue(UuidValidator::isValid($identityArchiveId));
        $this->assertTrue(UuidValidator::isValid($accountArchiveId));
        $this->assertNotSame((string) $identityId, $identityArchiveId);
        $this->assertNotSame((string) $accountId, $accountArchiveId);
        $identityModel = ArchivedIdentity::query()->findOrFail($identityArchiveId);
        $accountModel = ArchivedAccount::query()->findOrFail($accountArchiveId);
        $this->assertSame((string) $identityId, $identityModel->identity_id);
        $this->assertNull($identityModel->identity_created_at);
        $this->assertSame('ja', $identityModel->language);
        $this->assertSame((string) $accountId, $accountModel->account_id);
        $this->assertSame('general', $accountModel->account_category);
        $this->assertSame('individual', $accountModel->account_type);
        $this->assertSame($identity->archivedAt()->format('Y-m-d H:i:s'), $identityModel->archived_at->format('Y-m-d H:i:s'));
        $this->assertSame($account->archivedAt()->format('Y-m-d H:i:s'), $accountModel->archived_at->format('Y-m-d H:i:s'));

        $archiveIds = [];
        foreach (ArchivedPrincipalType::cases() as $type) {
            $beforeCreation = new DateTimeImmutable();
            $principal = $this->app()->make(ArchivedPrincipalFactoryInterface::class)->create($identityId, $type, $principalId, $accountId);
            $afterCreation = new DateTimeImmutable();
            $this->assertGreaterThanOrEqual($beforeCreation, $principal->archivedAt());
            $this->assertLessThanOrEqual($afterCreation, $principal->archivedAt());
            $this->app()->make(ArchivedPrincipalRepositoryInterface::class)->save($principal);
            $archiveId = (string) $principal->archivedPrincipalIdentifier();
            $this->assertTrue(UuidValidator::isValid($archiveId));
            $this->assertNotSame($principalId, $archiveId);
            $model = ArchivedPrincipal::query()->findOrFail($archiveId);
            $this->assertSame($principalId, $model->principal_id);
            $this->assertSame($type->value, $model->principal_type);
            $this->assertSame((string) $identityId, $model->identity_id);
            $this->assertSame((string) $accountId, $model->account_id);
            $this->assertSame($principal->archivedAt()->format('Y-m-d H:i:s'), $model->archived_at->format('Y-m-d H:i:s'));
            $archiveIds[] = $archiveId;
        }
        $this->assertCount(2, array_unique($archiveIds));
        $this->assertDatabaseCount('archived_principals', 2);
    }
}
