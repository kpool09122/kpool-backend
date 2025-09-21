<?php

declare(strict_types=1);

namespace Tests\Wiki\AccessControl\Application\UseCase\Command\ChangeAccessControl;

use Source\Wiki\AccessControl\Application\UseCase\Command\ChangeAccessControl\ChangeAccessControlInput;
use Source\Wiki\Shared\Domain\ValueObject\ActorIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Role;
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
        $actorIdentifier = new ActorIdentifier(StrTestHelper::generateUlid());
        $targetRole = Role::MEMBER_ACTOR;
        $input = new ChangeAccessControlInput(
            $holdingRole,
            $actorIdentifier,
            $targetRole,
        );
        $this->assertSame($holdingRole, $input->holdingRole());
        $this->assertSame((string)$actorIdentifier, (string)$input->actorIdentifier());
        $this->assertSame($targetRole, $input->targetRole());
    }
}
