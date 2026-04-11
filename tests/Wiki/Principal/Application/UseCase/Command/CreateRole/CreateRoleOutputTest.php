<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Application\UseCase\Command\CreateRole;

use DateTimeImmutable;
use Source\Wiki\Principal\Application\UseCase\Command\CreateRole\CreateRoleOutput;
use Source\Wiki\Principal\Domain\Entity\Role;
use Source\Wiki\Principal\Domain\ValueObject\PolicyIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\RoleIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class CreateRoleOutputTest extends TestCase
{
    /**
     * 正常系: RoleがセットされるとtoArrayが正しい値を返すこと.
     */
    public function testToArrayWithRole(): void
    {
        $roleIdentifier = new RoleIdentifier(StrTestHelper::generateUuid());
        $name = 'Test Role';
        $isSystemRole = false;
        $createdAt = new DateTimeImmutable();

        $role = new Role(
            $roleIdentifier,
            $name,
            [new PolicyIdentifier(StrTestHelper::generateUuid())],
            $isSystemRole,
            $createdAt,
        );

        $output = new CreateRoleOutput();
        $output->setRole($role);

        $result = $output->toArray();

        $this->assertSame((string) $roleIdentifier, $result['roleIdentifier']);
        $this->assertSame($name, $result['name']);
        $this->assertSame($isSystemRole, $result['isSystemRole']);
        $this->assertSame($createdAt->format('Y-m-d\TH:i:sP'), $result['createdAt']);
    }

    /**
     * 正常系: Roleが未セットの場合toArrayがnull値の配列を返すこと.
     */
    public function testToArrayWithoutRole(): void
    {
        $output = new CreateRoleOutput();

        $result = $output->toArray();

        $this->assertNull($result['roleIdentifier']);
        $this->assertNull($result['name']);
        $this->assertNull($result['isSystemRole']);
        $this->assertNull($result['createdAt']);
    }
}
