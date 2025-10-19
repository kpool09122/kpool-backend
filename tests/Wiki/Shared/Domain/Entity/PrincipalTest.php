<?php

declare(strict_types=1);

namespace Tests\Wiki\Shared\Domain\Entity;

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
        $role = Role::AGENCY_ACTOR;
        $agencyId = StrTestHelper::generateUlid();
        $groupIds = [
            StrTestHelper::generateUlid(),
            StrTestHelper::generateUlid(),
        ];
        $memberId = StrTestHelper::generateUlid();
        $principal = new Principal(
            $principalIdentifier,
            $role,
            $agencyId,
            $groupIds,
            $memberId,
        );
        $this->assertSame((string)$principalIdentifier, (string)$principal->principalIdentifier());
        $this->assertSame($role->value, $principal->role()->value);
        $this->assertSame($agencyId, $principal->agencyId());
        $this->assertSame($groupIds, $principal->groupIds());
        $this->assertSame($memberId, $principal->talentId());

        $principal = new Principal(
            $principalIdentifier,
            $role,
            null,
            [],
            null,
        );
        $this->assertNull($principal->agencyId());
        $this->assertEmpty($principal->groupIds());
        $this->assertNull($principal->talentId());
    }

    /**
     * 正常系：Roleのsetterが正しく動作すること.
     *
     * @return void
     */
    public function testSetRole(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $role = Role::AGENCY_ACTOR;
        $agencyId = StrTestHelper::generateUlid();
        $groupIds = [
            StrTestHelper::generateUlid(),
            StrTestHelper::generateUlid(),
        ];
        $memberId = StrTestHelper::generateUlid();
        $principal = new Principal(
            $principalIdentifier,
            $role,
            $agencyId,
            $groupIds,
            $memberId,
        );
        $this->assertSame($role, $principal->role());

        $newRole = Role::ADMINISTRATOR;
        $principal->setRole($newRole);
        $this->assertNotSame($role, $principal->role());
        $this->assertSame($newRole, $principal->role());
    }
}
