<?php

declare(strict_types=1);

namespace Tests\Monetization\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers;

use PHPUnit\Framework\TestCase;
use Source\Monetization\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers\PrincipalGroupMembers;
use Source\Monetization\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers\PrincipalStatusUpdate;
use Source\Monetization\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers\UpdatePrincipalGroupMembersInput;
use Source\Monetization\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Monetization\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\Monetization\Principal\Domain\ValueObject\PrincipalStatus;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

class UpdatePrincipalGroupMembersInputTest extends TestCase
{
    public function testPreservesInstancesAndDefaults(): void
    {
        $account = new AccountIdentifier('019c9b4c-0000-7000-8000-000000000001');
        $id = new PrincipalIdentifier('019c9b4c-0000-7000-8000-000000000002');
        $groups = [new PrincipalGroupMembers(new PrincipalGroupIdentifier('019c9b4c-0000-7000-8000-000000000003'), [$id])];
        $statuses = [new PrincipalStatusUpdate($id, PrincipalStatus::ACTIVE)];
        $subject = new UpdatePrincipalGroupMembersInput($account, $id, $groups, $statuses);
        self::assertSame($account, $subject->accountIdentifier());
        self::assertSame($id, $subject->operatorPrincipalIdentifier());
        self::assertSame($groups, $subject->principalGroups());
        self::assertSame($statuses, $subject->principalStatuses());
        self::assertSame([], (new UpdatePrincipalGroupMembersInput($account, $id, []))->principalStatuses());
    }
}
