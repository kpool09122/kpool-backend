<?php

declare(strict_types=1);

namespace Tests\Account\Account\Infrastructure\Repository;

use Application\Models\Account\ArchivedAccount as ArchivedModel;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Group;
use Source\Account\Account\Domain\Entity\ArchivedAccount;
use Source\Account\Account\Domain\ValueObject\ArchivedAccountIdentifier;
use Source\Account\Account\Infrastructure\Repository\ArchivedAccountRepository;
use Source\Account\Shared\Domain\ValueObject\AccountType;
use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Tests\TestCase;

#[Group('useDb')]
class ArchivedAccountRepositoryTest extends TestCase
{
    public function testPersistsArchiveWithoutSourceRecords(): void
    {
        $entity = new ArchivedAccount(new ArchivedAccountIdentifier('019c9b4c-0000-7000-8000-000000000001'), new AccountIdentifier('019c9b4c-0000-7000-8000-000000000001'), AccountCategory::AGENCY, AccountType::CORPORATION, new DateTimeImmutable('2026-10-03T01:02:03+00:00'));
        (new ArchivedAccountRepository())->save($entity);
        $model = ArchivedModel::query()->findOrFail((string) $entity->archivedAccountIdentifier());
        $this->assertSame((string) $entity->archivedAccountIdentifier(), $model->id);
        $this->assertSame((string) $entity->accountIdentifier(), $model->account_id);
        $this->assertSame($entity->accountCategory()->value, $model->account_category);
        $this->assertSame($entity->accountType()->value, $model->account_type);
        $this->assertSame($entity->archivedAt()->format('Y-m-d H:i:s'), $model->archived_at->format('Y-m-d H:i:s'));
    }
}
