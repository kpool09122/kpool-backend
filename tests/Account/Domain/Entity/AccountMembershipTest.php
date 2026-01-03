<?php

declare(strict_types=1);

namespace Tests\Account\Domain\Entity;

use PHPUnit\Framework\TestCase;
use Source\Account\Domain\Entity\AccountMembership;
use Source\Account\Domain\ValueObject\AccountRole;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;

class AccountMembershipTest extends TestCase
{
    public function test__construct(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $role = AccountRole::OWNER;
        $membership = new AccountMembership(
            $identityIdentifier,
            $role
        );
        $this->assertSame((string)$identityIdentifier, (string)$membership->identityIdentifier());
        $this->assertSame($role, $membership->role());
    }
}
