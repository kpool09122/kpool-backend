<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Application\UseCase\Command\AttachRoleToPrincipalGroup;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Principal\Application\UseCase\Command\AttachRoleToPrincipalGroup\AttachRoleToPrincipalGroupInput;
use Source\Wiki\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\RoleIdentifier;
use Tests\Helper\StrTestHelper;

class AttachRoleToPrincipalGroupInputTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     */
    public function test__construct(): void
    {
        $principalGroupIdentifier = new PrincipalGroupIdentifier(StrTestHelper::generateUuid());
        $roleIdentifier = new RoleIdentifier(StrTestHelper::generateUuid());

        $input = new AttachRoleToPrincipalGroupInput(
            $principalGroupIdentifier,
            $roleIdentifier,
        );

        $this->assertSame($principalGroupIdentifier, $input->principalGroupIdentifier());
        $this->assertSame($roleIdentifier, $input->roleIdentifier());
    }
}
