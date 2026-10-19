<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Domain\Entity;

use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Exception\PrincipalStatusChangeForbiddenException;
use Source\Wiki\Principal\Domain\ValueObject\PrincipalStatus;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

class PrincipalTest extends TestCase
{
    public function testPreservesIdentifiersAndDefaultsToActive(): void
    {
        $principalIdentifier = new PrincipalIdentifier('019c9b4c-0000-7000-8000-000000000001');
        $identityIdentifier = new IdentityIdentifier('019c9b4c-0000-7000-8000-000000000002');
        $accountIdentifier = new AccountIdentifier('019c9b4c-0000-7000-8000-000000000003');
        $principal = new Principal($principalIdentifier, $identityIdentifier, $accountIdentifier);
        self::assertSame($principalIdentifier, $principal->principalIdentifier());
        self::assertSame($identityIdentifier, $principal->identityIdentifier());
        self::assertSame($accountIdentifier, $principal->accountIdentifier());
        self::assertNull($principal->delegationIdentifier());
        self::assertFalse($principal->isDelegatedPrincipal());
        self::assertSame(PrincipalStatus::ACTIVE, $principal->status());
    }

    public function testDelegationIsPreservedAcrossSuspensionAndResumption(): void
    {
        $delegationIdentifier = new DelegationIdentifier('019c9b4c-0000-7000-8000-000000000004');
        $principal = new Principal(
            new PrincipalIdentifier('019c9b4c-0000-7000-8000-000000000001'),
            new IdentityIdentifier('019c9b4c-0000-7000-8000-000000000002'),
            new AccountIdentifier('019c9b4c-0000-7000-8000-000000000003'),
            $delegationIdentifier,
        );
        self::assertTrue($principal->isDelegatedPrincipal());
        self::assertSame(PrincipalStatus::ACTIVE, $principal->status());
        $principal->changeStatus(PrincipalStatus::SUSPENDED);
        self::assertSame(PrincipalStatus::SUSPENDED, $principal->status());
        $principal->changeStatus(PrincipalStatus::ACTIVE);
        self::assertSame(PrincipalStatus::ACTIVE, $principal->status());
        self::assertSame($delegationIdentifier, $principal->delegationIdentifier());
    }

    public function testForcedStoppedDelegatedPrincipalCannotResume(): void
    {
        $principal = new Principal(
            new PrincipalIdentifier('019c9b4c-0000-7000-8000-000000000001'),
            new IdentityIdentifier('019c9b4c-0000-7000-8000-000000000002'),
            new AccountIdentifier('019c9b4c-0000-7000-8000-000000000003'),
            new DelegationIdentifier('019c9b4c-0000-7000-8000-000000000004'),
            PrincipalStatus::FORCED_STOP,
        );
        $principal->changeStatus(PrincipalStatus::FORCED_STOP);
        self::assertSame(PrincipalStatus::FORCED_STOP, $principal->status());
        $this->expectException(PrincipalStatusChangeForbiddenException::class);
        $principal->changeStatus(PrincipalStatus::ACTIVE);
    }
}
