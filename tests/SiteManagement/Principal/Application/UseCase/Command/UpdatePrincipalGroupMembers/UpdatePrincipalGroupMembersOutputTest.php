<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers;

use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\PrincipalGroupDescription;
use Source\SiteManagement\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers\UpdatePrincipalGroupMembersOutput;
use Source\SiteManagement\Principal\Domain\Entity\PrincipalGroup;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;

class UpdatePrincipalGroupMembersOutputTest extends TestCase
{
    public function testUpdatedGroupSummariesCanBeReplaced(): void
    {
        $output = new UpdatePrincipalGroupMembersOutput();
        self::assertSame(['principalGroups' => []], $output->toArray());
        $group = new PrincipalGroup(
            new PrincipalGroupIdentifier(StrTestHelper::generateUuid()),
            'General',
            [],
            new AccountIdentifier(StrTestHelper::generateUuid()),
            true,
            new PrincipalGroupDescription('Group description'),
        );
        $group->replaceMembers([new PrincipalIdentifier(StrTestHelper::generateUuid())]);
        $output->setPrincipalGroups([$group]);
        self::assertSame(['principalGroups' => [[
            'principalGroupIdentifier' => (string) $group->principalGroupIdentifier(),
            'accountIdentifier' => (string) $group->accountIdentifier(),
            'name' => 'General',
            'description' => 'Group description',
            'isDefault' => true,
            'memberCount' => 1,
        ]]], $output->toArray());
        $output->setPrincipalGroups([]);
        self::assertSame(['principalGroups' => []], $output->toArray());
    }

    public function testGroupWithoutDescriptionOrMembers(): void
    {
        $output = new UpdatePrincipalGroupMembersOutput();
        $group = new PrincipalGroup(
            new PrincipalGroupIdentifier(StrTestHelper::generateUuid()),
            'Empty group',
            [],
            new AccountIdentifier(StrTestHelper::generateUuid()),
        );
        $output->setPrincipalGroups([$group]);
        self::assertNull($output->toArray()['principalGroups'][0]['description']);
        self::assertSame(0, $output->toArray()['principalGroups'][0]['memberCount']);
    }
}
