<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Principal\Infrastructure\Repository;

use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\SiteManagement\Principal\Domain\Entity\PrincipalGroup;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\SiteManagement\Principal\Infrastructure\Repository\PrincipalGroupRepository;
use Tests\Helper\CreateAccount;
use Tests\TestCase;

#[Group('useDb')]
class PrincipalGroupRepositoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        CreateAccount::create('00000000-0000-7000-8000-000000000009');
    }

    public function testSaveAndLoadRoundTrip(): void
    {
        $id = new PrincipalGroupIdentifier('69200000-0000-7000-8000-000000000099');
        $repository = new PrincipalGroupRepository();
        $repository->save(new PrincipalGroup($id, 'test', [], new AccountIdentifier('00000000-0000-7000-8000-000000000009')));
        $entity = $repository->findById($id);
        self::assertNotNull($entity);
        self::assertSame('test', $entity->name());
    }
}
