<?php

declare(strict_types=1);

namespace Tests\Analyze\Principal\Infrastructure\Repository;

use PHPUnit\Framework\Attributes\Group;
use Source\Analyze\Principal\Domain\Entity\Principal;
use Source\Analyze\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\Analyze\Principal\Domain\ValueObject\PrincipalStatus;
use Source\Analyze\Principal\Infrastructure\Repository\PrincipalRepository;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\CreateAccount;
use Tests\Helper\CreateIdentity;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class PrincipalRepositoryTest extends TestCase
{
    public function testPersistenceLookupAndDeletion(): void
    {
        $account = new AccountIdentifier(StrTestHelper::generateUuid());
        $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateAccount::create((string) $account);
        CreateIdentity::create($identity);
        $principal = new Principal(new PrincipalIdentifier(StrTestHelper::generateUuid()), $identity, $account, PrincipalStatus::ACTIVE);
        $repository = new PrincipalRepository();
        self::assertNull($repository->findByIdentityIdentifierAndAccountIdentifier($identity, $account));
        $repository->save($principal);
        $repository->save($principal);
        self::assertSame((string) $identity, (string) $repository->findById($principal->principalIdentifier())?->identityIdentifier());
        self::assertSame([], $repository->findByIds([]));
        self::assertCount(1, $repository->findByIds([$principal->principalIdentifier()]));
        self::assertCount(1, $repository->findAllByIdentityIdentifier($identity));
        $repository->delete($principal);
        self::assertNull($repository->findById($principal->principalIdentifier()));
    }
}
