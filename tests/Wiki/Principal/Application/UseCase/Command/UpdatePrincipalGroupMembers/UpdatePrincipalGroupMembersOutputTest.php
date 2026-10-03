<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Wiki\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers\UpdatePrincipalGroupMembersOutput;
use Source\Wiki\Principal\Domain\Entity\PrincipalGroup;
use Source\Wiki\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

class UpdatePrincipalGroupMembersOutputTest extends TestCase
{
    public function testSerializesGroupAndMemberCount(): void
    {
        $group = new PrincipalGroup(new PrincipalGroupIdentifier('019c9b4c-0000-7000-8000-000000000001'), new AccountIdentifier('019c9b4c-0000-7000-8000-000000000002'), 'Editors', false, new DateTimeImmutable('2026-10-03T01:02:03+00:00'));
        $group->addMember(new PrincipalIdentifier('019c9b4c-0000-7000-8000-000000000003'));
        $output = new UpdatePrincipalGroupMembersOutput();
        $this->assertSame(['principalGroups' => []], $output->toArray());
        $output->setPrincipalGroups([$group]);
        $this->assertSame(['principalGroups' => [[
            'principalGroupIdentifier' => '019c9b4c-0000-7000-8000-000000000001',
            'accountIdentifier' => '019c9b4c-0000-7000-8000-000000000002',
            'name' => 'Editors',
            'isDefault' => false,
            'memberCount' => 1,
            'createdAt' => '2026-10-03T01:02:03+00:00',
        ]]], $output->toArray());
    }
}
