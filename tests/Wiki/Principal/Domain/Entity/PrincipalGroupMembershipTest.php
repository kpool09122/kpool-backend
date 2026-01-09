<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Domain\Entity;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Principal\Domain\Entity\PrincipalGroupMembership;
use Source\Wiki\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;

class PrincipalGroupMembershipTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     */
    public function test__construct(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principalGroupIdentifier = new PrincipalGroupIdentifier(StrTestHelper::generateUuid());

        $membership = new PrincipalGroupMembership(
            $principalIdentifier,
            $principalGroupIdentifier,
        );

        $this->assertSame($principalIdentifier, $membership->principalIdentifier());
        $this->assertSame($principalGroupIdentifier, $membership->principalGroupIdentifier());
    }
}
