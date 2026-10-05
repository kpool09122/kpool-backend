<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Principal\Infrastructure\Repository;

use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\SiteManagement\Principal\Domain\Entity\Principal;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\SiteManagement\Principal\Infrastructure\Repository\PrincipalRepository;
use Tests\Helper\CreateIdentity;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class PrincipalRepositoryTest extends TestCase
{
    public function testSaveOnlyPersistsAndFindsTypedPrincipal(): void
    {
        $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identity);
        $id = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $repository = new PrincipalRepository();
        $repository->save(new Principal($id, $identity));
        $repository->save(new Principal($id, $identity));
        self::assertSame((string) $identity, (string) $repository->findById($id)?->identityIdentifier());
        $this->assertDatabaseMissing('site_management_principal_group_memberships', ['principal_id' => (string) $id]);
        $repository->delete(new Principal($id, $identity));
        self::assertNull($repository->findById($id));
    }
}
