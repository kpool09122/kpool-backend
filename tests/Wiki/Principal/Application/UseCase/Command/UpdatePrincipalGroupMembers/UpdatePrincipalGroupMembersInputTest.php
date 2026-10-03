<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers;

use PHPUnit\Framework\TestCase;
use Source\Account\Shared\Domain\ValueObject\AccountType;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Wiki\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers\UpdatePrincipalGroupMembersInput;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

class UpdatePrincipalGroupMembersInputTest extends TestCase
{
    public function testPreservesSuppliedValues(): void
    {
        $accountIdentifier = new AccountIdentifier('019c9b4c-0000-7000-8000-000000000001');
        $operatorPrincipalIdentifier = new PrincipalIdentifier('019c9b4c-0000-7000-8000-000000000001');
        $principalGroups = [];
        $accountType = AccountType::CORPORATION;

        $subject = new UpdatePrincipalGroupMembersInput($accountIdentifier, $operatorPrincipalIdentifier, $principalGroups, $accountType);

        $this->assertSame($accountIdentifier, $subject->accountIdentifier());
        $this->assertSame($operatorPrincipalIdentifier, $subject->operatorPrincipalIdentifier());
        $this->assertSame($principalGroups, $subject->principalGroups());
        $this->assertSame($accountType, $subject->accountType());
    }
}
