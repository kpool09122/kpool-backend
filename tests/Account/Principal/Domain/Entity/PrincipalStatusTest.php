<?php

declare(strict_types=1);

namespace Tests\Account\Principal\Domain\Entity;

use PHPUnit\Framework\TestCase;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Principal\Domain\Exception\PrincipalStatusChangeForbiddenException;
use Source\Account\Principal\Domain\ValueObject\PrincipalStatus;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

class PrincipalStatusTest extends TestCase
{
    private function principal(PrincipalStatus $status = PrincipalStatus::ACTIVE): Principal
    {
        return new Principal(
            new PrincipalIdentifier('019c9b4c-0000-7000-8000-000000000001'),
            new IdentityIdentifier('019c9b4c-0000-7000-8000-000000000002'),
            new AccountIdentifier('019c9b4c-0000-7000-8000-000000000003'),
            status: $status,
        );
    }

    public function testDefaultsToActive(): void
    {
        $principal = new Principal(
            new PrincipalIdentifier('019c9b4c-0000-7000-8000-000000000001'),
            new IdentityIdentifier('019c9b4c-0000-7000-8000-000000000002'),
            new AccountIdentifier('019c9b4c-0000-7000-8000-000000000003'),
        );
        self::assertSame(PrincipalStatus::ACTIVE, $principal->status());
    }

    public function testSuspensionAndResumptionPreserveIdentity(): void
    {
        $principal = $this->principal();
        $identifier = $principal->principalIdentifier();
        $principal->changeStatus(PrincipalStatus::SUSPENDED);
        self::assertSame(PrincipalStatus::SUSPENDED, $principal->status());
        $principal->changeStatus(PrincipalStatus::ACTIVE);
        self::assertSame(PrincipalStatus::ACTIVE, $principal->status());
        self::assertSame($identifier, $principal->principalIdentifier());
    }

    public function testUnchangedForcedStopIsAllowed(): void
    {
        $principal = $this->principal(PrincipalStatus::FORCED_STOP);
        $principal->changeStatus(PrincipalStatus::FORCED_STOP);
        self::assertSame(PrincipalStatus::FORCED_STOP, $principal->status());
    }

    public function testForcedStopCannotBeResumed(): void
    {
        $this->expectException(PrincipalStatusChangeForbiddenException::class);
        $this->principal(PrincipalStatus::FORCED_STOP)->changeStatus(PrincipalStatus::ACTIVE);
    }

    public function testForcedStopCannotBeSuspended(): void
    {
        $this->expectException(PrincipalStatusChangeForbiddenException::class);
        $this->principal(PrincipalStatus::FORCED_STOP)->changeStatus(PrincipalStatus::SUSPENDED);
    }

    public function testEveryStatusAcceptsAnUnchangedValue(): void
    {
        foreach (PrincipalStatus::cases() as $status) {
            $principal = $this->principal($status);
            $principal->changeStatus($status);
            self::assertSame($status, $principal->status());
        }
    }

    public function testManagementCannotSetForcedStop(): void
    {
        $this->expectException(PrincipalStatusChangeForbiddenException::class);
        $this->principal()->changeStatus(PrincipalStatus::FORCED_STOP);
    }
}
