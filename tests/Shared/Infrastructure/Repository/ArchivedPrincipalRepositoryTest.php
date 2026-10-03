<?php

declare(strict_types=1);

namespace Tests\Shared\Infrastructure\Repository;

use Application\Models\Shared\ArchivedPrincipal as ArchivedModel;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\Entity\ArchivedPrincipal;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\ArchivedPrincipalIdentifier;
use Source\Shared\Domain\ValueObject\ArchivedPrincipalType;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Infrastructure\Repository\ArchivedPrincipalRepository;
use Tests\TestCase;

#[Group('useDb')]
class ArchivedPrincipalRepositoryTest extends TestCase
{
    public function testPersistsArchiveWithoutSourceRecords(): void
    {
        $entity = new ArchivedPrincipal(new ArchivedPrincipalIdentifier('019c9b4c-0000-7000-8000-000000000001'), new IdentityIdentifier('019c9b4c-0000-7000-8000-000000000001'), ArchivedPrincipalType::ACCOUNT, '019c9b4c-0000-7000-8000-000000000002', new AccountIdentifier('019c9b4c-0000-7000-8000-000000000001'), new DateTimeImmutable('2026-10-03T01:02:03+00:00'));
        DB::table('archived_identities')->insert([
            'id' => '019c9b4c-0000-7000-8000-000000000003',
            'identity_id' => (string) $entity->identityIdentifier(),
            'language' => 'ja',
            'identity_created_at' => null,
            'archived_at' => $entity->archivedAt(),
        ]);
        (new ArchivedPrincipalRepository())->save($entity);
        $model = ArchivedModel::query()->findOrFail((string) $entity->archivedPrincipalIdentifier());
        $this->assertSame((string) $entity->archivedPrincipalIdentifier(), $model->id);
        $this->assertSame((string) $entity->identityIdentifier(), $model->identity_id);
        $this->assertSame($entity->principalType()->value, $model->principal_type);
        $this->assertSame($entity->principalId(), $model->principal_id);
        $this->assertSame((string) $entity->accountIdentifier(), $model->account_id);
        $this->assertSame($entity->archivedAt()->format('Y-m-d H:i:s'), $model->archived_at->format('Y-m-d H:i:s'));
    }
}
