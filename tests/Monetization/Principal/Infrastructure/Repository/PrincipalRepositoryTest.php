<?php

declare(strict_types=1);

namespace Tests\Monetization\Principal\Infrastructure\Repository;

use PHPUnit\Framework\Attributes\Group;
use Source\Monetization\Principal\Domain\Entity\Principal;
use Source\Monetization\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\Monetization\Principal\Domain\ValueObject\PrincipalStatus;
use Source\Monetization\Principal\Infrastructure\Repository\PrincipalRepository;
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
        $principals = $repository->findByIds([$principal->principalIdentifier(), new PrincipalIdentifier(StrTestHelper::generateUuid())]);
        self::assertSame([(string) $principal->principalIdentifier()], array_keys($principals));
        self::assertEquals($principal, $principals[(string) $principal->principalIdentifier()]);
        self::assertEquals($principal, $repository->findById($principal->principalIdentifier()));
        self::assertEquals($principal, $repository->findByIdentityIdentifierAndAccountIdentifier($identity, $account));
        self::assertEquals([$principal], $repository->findAllByIdentityIdentifier($identity));
        self::assertCount(1, $repository->findAllByIdentityIdentifier($identity));
        $repository->delete($principal);
        self::assertNull($repository->findById($principal->principalIdentifier()));
    }
}
