<?php

declare(strict_types=1);

namespace Tests\Wiki\AccessControl\Application\UseCase\Command\CreatePrincipal;

use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\AccessControl\Application\UseCase\Command\CreatePrincipal\CreatePrincipalInput;
use Source\Wiki\Shared\Domain\ValueObject\Role;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class CreatePrincipalInputTest extends TestCase
{
    public function test__construct(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUlid());
        $role = Role::ADMINISTRATOR;
        $agencyId = StrTestHelper::generateUlid();
        $groupIds = [StrTestHelper::generateUlid()];
        $talentIds = [StrTestHelper::generateUlid()];

        $input = new CreatePrincipalInput(
            $identityIdentifier,
            $role,
            $agencyId,
            $groupIds,
            $talentIds,
        );

        $this->assertSame((string) $identityIdentifier, (string) $input->identityIdentifier());
        $this->assertSame($role, $input->role());
        $this->assertSame($agencyId, $input->agencyId());
        $this->assertSame($groupIds, $input->groupIds());
        $this->assertSame($talentIds, $input->talentIds());
    }

    public function test__constructWithDefaults(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUlid());
        $role = Role::NONE;

        $input = new CreatePrincipalInput(
            $identityIdentifier,
            $role,
        );

        $this->assertNull($input->agencyId());
        $this->assertEmpty($input->groupIds());
        $this->assertEmpty($input->talentIds());
    }
}
