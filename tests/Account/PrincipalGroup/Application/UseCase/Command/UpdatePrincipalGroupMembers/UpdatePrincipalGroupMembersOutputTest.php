<?php

declare(strict_types=1);

namespace Tests\Account\PrincipalGroup\Application\UseCase\Command\UpdatePrincipalGroupMembers;

use DateTimeImmutable;
use Source\Account\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers\UpdatePrincipalGroupMembersOutput;
use Source\Account\Principal\Domain\Entity\PrincipalGroup;
use Source\Account\Principal\Domain\ValueObject\RoleIdentifier;
use Source\Account\Shared\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class UpdatePrincipalGroupMembersOutputTest extends TestCase
{
    public function testToArrayWithPrincipalGroups(): void
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $principalGroupIdentifierA = new PrincipalGroupIdentifier(StrTestHelper::generateUuid());
        $principalGroupIdentifierB = new PrincipalGroupIdentifier(StrTestHelper::generateUuid());
        $principalIdentifierA = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principalIdentifierB = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $roleIdentifier = new RoleIdentifier(StrTestHelper::generateUuid());
        $principalGroupA = new PrincipalGroup(
            $principalGroupIdentifierA,
            $accountIdentifier,
            'Managers',
            true,
            new DateTimeImmutable(),
        );
        $principalGroupA->addRole($roleIdentifier);
        $principalGroupA->addMember($principalIdentifierA);
        $principalGroupB = new PrincipalGroup(
            $principalGroupIdentifierB,
            $accountIdentifier,
            'Members',
            false,
            new DateTimeImmutable(),
        );
        $principalGroupB->addMember($principalIdentifierB);

        $output = new UpdatePrincipalGroupMembersOutput();
        $output->setPrincipalGroups([$principalGroupA, $principalGroupB]);

        $result = $output->toArray();

        $this->assertCount(2, $result['principalGroups']);
        $this->assertSame((string) $principalGroupIdentifierA, $result['principalGroups'][0]['principalGroupIdentifier']);
        $this->assertSame((string) $accountIdentifier, $result['principalGroups'][0]['accountIdentifier']);
        $this->assertSame('Managers', $result['principalGroups'][0]['name']);
        $this->assertSame([(string) $roleIdentifier], $result['principalGroups'][0]['roleIdentifiers']);
        $this->assertTrue($result['principalGroups'][0]['isDefault']);
        $this->assertSame([(string) $principalIdentifierA], $result['principalGroups'][0]['members']);
        $this->assertSame((string) $principalGroupIdentifierB, $result['principalGroups'][1]['principalGroupIdentifier']);
        $this->assertSame([], $result['principalGroups'][1]['roleIdentifiers']);
        $this->assertFalse($result['principalGroups'][1]['isDefault']);
        $this->assertSame([(string) $principalIdentifierB], $result['principalGroups'][1]['members']);
    }

    public function testToArrayWithoutPrincipalGroups(): void
    {
        $output = new UpdatePrincipalGroupMembersOutput();

        $this->assertSame(['principalGroups' => []], $output->toArray());
    }
}
