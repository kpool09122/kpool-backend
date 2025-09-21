<?php

declare(strict_types=1);

namespace Tests\Wiki\Shared\Domain\Entity;

use Source\Wiki\Shared\Domain\Entity\Actor;
use Source\Wiki\Shared\Domain\ValueObject\ActorIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Role;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ActorTest extends TestCase
{
    /**
     * 正常系：正しくインスタンスが作成できること.
     *
     * @return void
     */
    public function test__construct(): void
    {
        $actorIdentifier = new ActorIdentifier(StrTestHelper::generateUlid());
        $role = Role::AGENCY_ACTOR;
        $agencyId = StrTestHelper::generateUlid();
        $groupIds = [
            StrTestHelper::generateUlid(),
            StrTestHelper::generateUlid(),
        ];
        $memberId = StrTestHelper::generateUlid();
        $actor = new Actor(
            $actorIdentifier,
            $role,
            $agencyId,
            $groupIds,
            $memberId,
        );
        $this->assertSame((string)$actorIdentifier, (string)$actor->actorIdentifier());
        $this->assertSame($role->value, $actor->role()->value);
        $this->assertSame($agencyId, $actor->agencyId());
        $this->assertSame($groupIds, $actor->groupIds());
        $this->assertSame($memberId, $actor->memberId());

        $actor = new Actor(
            $actorIdentifier,
            $role,
            null,
            [],
            null,
        );
        $this->assertNull($actor->agencyId());
        $this->assertEmpty($actor->groupIds());
        $this->assertNull($actor->memberId());
    }

    /**
     * 正常系：Roleのsetterが正しく動作すること.
     *
     * @return void
     */
    public function testSetRole(): void
    {
        $actorIdentifier = new ActorIdentifier(StrTestHelper::generateUlid());
        $role = Role::AGENCY_ACTOR;
        $agencyId = StrTestHelper::generateUlid();
        $groupIds = [
            StrTestHelper::generateUlid(),
            StrTestHelper::generateUlid(),
        ];
        $memberId = StrTestHelper::generateUlid();
        $actor = new Actor(
            $actorIdentifier,
            $role,
            $agencyId,
            $groupIds,
            $memberId,
        );
        $this->assertSame($role, $actor->role());

        $newRole = Role::ADMINISTRATOR;
        $actor->setRole($newRole);
        $this->assertNotSame($role, $actor->role());
        $this->assertSame($newRole, $actor->role());
    }
}
