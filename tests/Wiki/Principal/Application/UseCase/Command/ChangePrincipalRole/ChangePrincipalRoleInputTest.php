<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Application\UseCase\Command\ChangePrincipalRole;

use Source\Wiki\Principal\Application\UseCase\Command\ChangePrincipalRole\ChangePrincipalRoleInput;
use Source\Wiki\Principal\Domain\ValueObject\Role;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ChangePrincipalRoleInputTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $operatorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $targetPrincipalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $targetRole = Role::TALENT_ACTOR;
        $input = new ChangePrincipalRoleInput(
            $operatorIdentifier,
            $targetPrincipalIdentifier,
            $targetRole,
        );
        $this->assertSame($operatorIdentifier, $input->operatorIdentifier());
        $this->assertSame($targetPrincipalIdentifier, $input->targetPrincipalIdentifier());
        $this->assertSame($targetRole, $input->targetRole());
    }
}
