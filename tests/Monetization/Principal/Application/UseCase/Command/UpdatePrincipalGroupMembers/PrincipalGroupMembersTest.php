<?php

declare(strict_types=1);

namespace Tests\Monetization\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers;

use PHPUnit\Framework\TestCase;
use Source\Monetization\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers\PrincipalGroupMembers;
use Source\Monetization\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Monetization\Principal\Domain\ValueObject\PrincipalIdentifier;

class PrincipalGroupMembersTest extends TestCase
{
    public function testPreservesInstances(): void
    {
        $id = new PrincipalGroupIdentifier('019c9b4c-0000-7000-8000-000000000001');
        $members = [new PrincipalIdentifier('019c9b4c-0000-7000-8000-000000000002')];
        $subject = new PrincipalGroupMembers($id, $members);
        self::assertSame($id, $subject->principalGroupIdentifier());
        self::assertSame($members, $subject->principalIdentifiers());
    }
}
