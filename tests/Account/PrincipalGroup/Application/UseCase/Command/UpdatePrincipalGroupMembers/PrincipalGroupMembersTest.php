<?php

declare(strict_types=1);

namespace Tests\Account\PrincipalGroup\Application\UseCase\Command\UpdatePrincipalGroupMembers;

use Source\Account\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers\PrincipalGroupMembers;
use Source\Account\Shared\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class PrincipalGroupMembersTest extends TestCase
{
    public function test__construct(): void
    {
        $principalGroupIdentifier = new PrincipalGroupIdentifier(StrTestHelper::generateUuid());
        $principalIdentifierA = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principalIdentifierB = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $principalGroupMembers = new PrincipalGroupMembers(
            $principalGroupIdentifier,
            [$principalIdentifierA, $principalIdentifierB],
        );

        $this->assertSame($principalGroupIdentifier, $principalGroupMembers->principalGroupIdentifier());
        $this->assertSame([$principalIdentifierA, $principalIdentifierB], $principalGroupMembers->principalIdentifiers());
    }
}
