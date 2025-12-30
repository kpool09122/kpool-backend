<?php

declare(strict_types=1);

namespace Tests\Wiki\AccessControl\Application\UseCase\Command\ChangeAccessControl;

use Source\Wiki\AccessControl\Application\UseCase\Command\ChangeAccessControl\ChangeAccessControlInput;
use Source\Wiki\Principal\Domain\ValueObject\Role;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ChangeAccessControlInputTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $holdingRole = Role::ADMINISTRATOR;
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $targetRole = Role::TALENT_ACTOR;
        $input = new ChangeAccessControlInput(
            $holdingRole,
            $principalIdentifier,
            $targetRole,
        );
        $this->assertSame($holdingRole, $input->holdingRole());
        $this->assertSame((string)$principalIdentifier, (string)$input->principalIdentifier());
        $this->assertSame($targetRole, $input->targetRole());
    }
}
