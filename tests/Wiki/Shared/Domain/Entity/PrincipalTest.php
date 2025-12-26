<?php

declare(strict_types=1);

namespace Tests\Wiki\Shared\Domain\Entity;

use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Shared\Domain\Entity\Principal;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Role;
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
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUlid());
        $role = Role::AGENCY_ACTOR;
        $agencyId = StrTestHelper::generateUlid();
        $groupIds = [
            StrTestHelper::generateUlid(),
            StrTestHelper::generateUlid(),
        ];
        $memberIds = [StrTestHelper::generateUlid()];
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
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUlid());
        $role = Role::AGENCY_ACTOR;
        $agencyId = StrTestHelper::generateUlid();
        $groupIds = [
            StrTestHelper::generateUlid(),
            StrTestHelper::generateUlid(),
        ];
        $memberIds = [StrTestHelper::generateUlid()];
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
}
