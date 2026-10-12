<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers;

use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\SiteManagement\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers\PrincipalGroupMembers;
use Source\SiteManagement\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers\UpdatePrincipalGroupMembersInput;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;

class UpdatePrincipalGroupMembersInputTest extends TestCase
{
    public function testPreservesSuppliedValues(): void
    {
        $accountIdentifier = new AccountIdentifier('019c9b4c-0000-7000-8000-000000000001');
        $operatorPrincipalIdentifier = new PrincipalIdentifier('019c9b4c-0000-7000-8000-000000000001');
        $groupIdentifier = new PrincipalGroupIdentifier('019c9b4c-0000-7000-8000-000000000002');
        $members = [$operatorPrincipalIdentifier];
        $group = new PrincipalGroupMembers($groupIdentifier, $members);
        $principalGroups = [$group];
        self::assertSame($groupIdentifier, $group->principalGroupIdentifier());
        self::assertSame($members, $group->principalIdentifiers());

        $subject = new UpdatePrincipalGroupMembersInput($accountIdentifier, $operatorPrincipalIdentifier, $principalGroups);

        $this->assertSame($accountIdentifier, $subject->accountIdentifier());
        $this->assertSame($operatorPrincipalIdentifier, $subject->operatorPrincipalIdentifier());
        $this->assertSame($principalGroups, $subject->principalGroups());
    }
}
