<?php

declare(strict_types=1);

namespace Tests\Monetization\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers;

use PHPUnit\Framework\TestCase;
use Source\Monetization\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers\UpdatePrincipalGroupMembersOutput;
use Source\Monetization\Principal\Domain\Entity\Principal;
use Source\Monetization\Principal\Domain\Entity\PrincipalGroup;
use Source\Monetization\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Monetization\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\Monetization\Principal\Domain\ValueObject\PrincipalStatus;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

class UpdatePrincipalGroupMembersOutputTest extends TestCase
{
    public function testSerializesGroupsStatusesAndEmptyArrays(): void
    {
        $account = new AccountIdentifier('019c9b4c-0000-7000-8000-000000000001');
        $id = new PrincipalIdentifier('019c9b4c-0000-7000-8000-000000000002');
        $groupId = new PrincipalGroupIdentifier('019c9b4c-0000-7000-8000-000000000003');
        $output = new UpdatePrincipalGroupMembersOutput();
        self::assertSame(['principalStatuses' => [], 'principalGroups' => []], $output->toArray());
        $output->setPrincipalGroups([new PrincipalGroup($groupId, 'Managers', [], $account, false, null, [$id])]);
        $output->setPrincipalStatuses([new Principal($id, new IdentityIdentifier('019c9b4c-0000-7000-8000-000000000004'), $account, PrincipalStatus::SUSPENDED)]);
        self::assertSame(['principalStatuses' => [['principalIdentifier' => (string) $id, 'status' => 'suspended']], 'principalGroups' => [['principalGroupIdentifier' => (string) $groupId, 'accountIdentifier' => (string) $account, 'name' => 'Managers', 'description' => null, 'isDefault' => false, 'memberCount' => 1]]], $output->toArray());
    }
}
