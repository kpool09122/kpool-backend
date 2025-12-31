<?php

declare(strict_types=1);

namespace Tests\Account\Domain\Entity;

use PHPUnit\Framework\TestCase;
use Source\Account\Domain\Entity\AccountMembership;
use Source\Account\Domain\ValueObject\AccountRole;
use Source\Shared\Domain\ValueObject\UserIdentifier;
use Tests\Helper\StrTestHelper;

class AccountMembershipTest extends TestCase
{
    public function test__construct(): void
    {
        $userIdentifier = new UserIdentifier(StrTestHelper::generateUuid());
        $role = AccountRole::OWNER;
        $membership = new AccountMembership(
            $userIdentifier,
            $role
        );
        $this->assertSame((string)$userIdentifier, (string)$membership->userIdentifier());
        $this->assertSame($role, $membership->role());
    }
}
