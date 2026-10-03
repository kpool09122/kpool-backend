<?php

declare(strict_types=1);

namespace Tests\Identity\Infrastructure\Repository;

use Application\Models\Identity\ArchivedIdentity as ArchivedModel;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Group;
use Source\Identity\Domain\Entity\ArchivedIdentity;
use Source\Identity\Domain\ValueObject\ArchivedIdentityIdentifier;
use Source\Identity\Infrastructure\Repository\ArchivedIdentityRepository;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Tests\TestCase;

#[Group('useDb')]
class ArchivedIdentityRepositoryTest extends TestCase
{
    public function testPersistsArchiveWithoutSourceRecords(): void
    {
        $entity = new ArchivedIdentity(new ArchivedIdentityIdentifier('019c9b4c-0000-7000-8000-000000000001'), new IdentityIdentifier('019c9b4c-0000-7000-8000-000000000001'), Language::JAPANESE, new DateTimeImmutable('2026-10-03T01:02:03+00:00'), new DateTimeImmutable('2026-10-03T01:02:03+00:00'));
        (new ArchivedIdentityRepository())->save($entity);
        $model = ArchivedModel::query()->findOrFail((string) $entity->archivedIdentityIdentifier());
        $this->assertSame((string) $entity->archivedIdentityIdentifier(), $model->id);
        $this->assertSame((string) $entity->identityIdentifier(), $model->identity_id);
        $this->assertSame($entity->language()->value, $model->language);
        $this->assertNotNull($entity->identityCreatedAt());
        $this->assertNotNull($model->identity_created_at);
        $this->assertSame($entity->identityCreatedAt()->format('Y-m-d H:i:s'), $model->identity_created_at->format('Y-m-d H:i:s'));
        $this->assertSame($entity->archivedAt()->format('Y-m-d H:i:s'), $model->archived_at->format('Y-m-d H:i:s'));
    }

    public function testAllowsUnknownIdentityCreationTime(): void
    {
        $entity = new ArchivedIdentity(new ArchivedIdentityIdentifier('019c9b4c-0000-7000-8000-000000000002'), new IdentityIdentifier('019c9b4c-0000-7000-8000-000000000001'), Language::JAPANESE, null, new DateTimeImmutable('2026-10-03'));
        (new ArchivedIdentityRepository())->save($entity);
        $this->assertNull(ArchivedModel::query()->findOrFail((string) $entity->archivedIdentityIdentifier())->identity_created_at);
    }
}
