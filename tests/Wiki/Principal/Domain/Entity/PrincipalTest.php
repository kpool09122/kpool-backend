<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Domain\Entity;

use DomainException;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\ValueObject\Role;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class PrincipalTest extends TestCase
{
    /**
     * 正常系：正しくインスタンスが作成できること.
     *
     * @return void
     */
    public function test__construct(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $role = Role::AGENCY_ACTOR;
        $agencyId = StrTestHelper::generateUuid();
        $groupIds = [
            StrTestHelper::generateUuid(),
            StrTestHelper::generateUuid(),
        ];
        $memberIds = [StrTestHelper::generateUuid()];
        $principal = new Principal(
            $principalIdentifier,
            $identityIdentifier,
            $role,
            $agencyId,
            $groupIds,
            $memberIds,
        );
        $this->assertSame((string)$principalIdentifier, (string)$principal->principalIdentifier());
        $this->assertSame((string)$identityIdentifier, (string)$principal->identityIdentifier());
        $this->assertSame($role->value, $principal->role()->value);
        $this->assertSame($agencyId, $principal->agencyId());
        $this->assertSame($groupIds, $principal->groupIds());
        $this->assertSame($memberIds, $principal->talentIds());

        $principal = new Principal(
            $principalIdentifier,
            $identityIdentifier,
            $role,
            null,
            [],
            [],
        );
        $this->assertNull($principal->agencyId());
        $this->assertEmpty($principal->groupIds());
        $this->assertEmpty($principal->talentIds());
    }

    /**
     * 正常系：Roleのsetterが正しく動作すること.
     *
     * @return void
     */
    public function testSetRole(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $role = Role::AGENCY_ACTOR;
        $agencyId = StrTestHelper::generateUuid();
        $groupIds = [
            StrTestHelper::generateUuid(),
            StrTestHelper::generateUuid(),
        ];
        $memberIds = [StrTestHelper::generateUuid()];
        $principal = new Principal(
            $principalIdentifier,
            $identityIdentifier,
            $role,
            $agencyId,
            $groupIds,
            $memberIds,
        );
        $this->assertSame($role, $principal->role());

        $newRole = Role::ADMINISTRATOR;
        $principal->setRole($newRole);
        $this->assertNotSame($role, $principal->role());
        $this->assertSame($newRole, $principal->role());
    }

    /**
     * 正常系：通常のPrincipalはdelegationIdentifierがnullでenabledがtrueであること.
     */
    public function testNonDelegatedPrincipal(): void
    {
        $principal = new Principal(
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            Role::TALENT_ACTOR,
            null,
            [],
            [],
        );

        $this->assertNull($principal->delegationIdentifier());
        $this->assertFalse($principal->isDelegatedPrincipal());
        $this->assertTrue($principal->isEnabled());
    }

    /**
     * 正常系：代理用PrincipalはdelegationIdentifierを持ち、isDelegatedPrincipalがtrueになること.
     */
    public function testDelegatedPrincipal(): void
    {
        $delegationIdentifier = new DelegationIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal(
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            Role::TALENT_ACTOR,
            null,
            [],
            [],
            $delegationIdentifier,
            true,
        );

        $this->assertSame($delegationIdentifier, $principal->delegationIdentifier());
        $this->assertTrue($principal->isDelegatedPrincipal());
        $this->assertTrue($principal->isEnabled());
    }

    /**
     * 正常系：代理用PrincipalのsetEnabledが正しく動作すること.
     */
    public function testSetEnabledOnDelegatedPrincipal(): void
    {
        $delegationIdentifier = new DelegationIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal(
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            Role::TALENT_ACTOR,
            null,
            [],
            [],
            $delegationIdentifier,
            true,
        );

        $this->assertTrue($principal->isEnabled());

        $principal->setEnabled(false);
        $this->assertFalse($principal->isEnabled());

        $principal->setEnabled(true);
        $this->assertTrue($principal->isEnabled());
    }

    /**
     * 異常系：通常のPrincipalでsetEnabledを呼ぶと例外が発生すること.
     */
    public function testSetEnabledOnNonDelegatedPrincipalThrowsException(): void
    {
        $principal = new Principal(
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            Role::TALENT_ACTOR,
            null,
            [],
            [],
        );

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Cannot change enabled status of non-delegated principal.');

        $principal->setEnabled(false);
    }
}
