<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Application\UseCase\Command\DetachPolicyFromRole;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Principal\Application\UseCase\Command\DetachPolicyFromRole\DetachPolicyFromRoleInput;
use Source\Wiki\Principal\Domain\ValueObject\PolicyIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\RoleIdentifier;

class DetachPolicyFromRoleInputTest extends TestCase
{
    public function testPreservesSuppliedValues(): void
    {
        $roleIdentifier = new RoleIdentifier('019c9b4c-0000-7000-8000-000000000001');
        $policyIdentifier = new PolicyIdentifier('019c9b4c-0000-7000-8000-000000000001');

        $subject = new DetachPolicyFromRoleInput($roleIdentifier, $policyIdentifier);

        $this->assertSame($roleIdentifier, $subject->roleIdentifier());
        $this->assertSame($policyIdentifier, $subject->policyIdentifier());
    }
}
