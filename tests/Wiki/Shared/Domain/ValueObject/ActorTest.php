<?php

declare(strict_types=1);

namespace Tests\Wiki\Shared\Domain\ValueObject;

use Source\Wiki\Shared\Domain\ValueObject\Actor;
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
        $role = Role::AGENCY_ACTOR;
        $agencyId = StrTestHelper::generateUlid();
        $groupIds = [
            StrTestHelper::generateUlid(),
            StrTestHelper::generateUlid(),
        ];
        $memberId = StrTestHelper::generateUlid();
        $actor = new Actor(
            $role,
            $agencyId,
            $groupIds,
            $memberId,
        );
        $this->assertSame($role->value, $actor->role()->value);
        $this->assertSame($agencyId, $actor->agencyId());
        $this->assertSame($groupIds, $actor->groupIds());
        $this->assertSame($memberId, $actor->memberId());

        $actor = new Actor(
            $role,
            null,
            [],
            null,
        );
        $this->assertNull($actor->agencyId());
        $this->assertEmpty($actor->groupIds());
        $this->assertNull($actor->memberId());
    }
}
