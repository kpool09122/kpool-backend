<?php

declare(strict_types=1);

namespace Tests\Account\Principal\Domain\Entity;

use DateTimeImmutable;
use Source\Account\Principal\Domain\Entity\Policy;
use Source\Account\Principal\Domain\ValueObject\Action;
use Source\Account\Principal\Domain\ValueObject\Effect;
use Source\Account\Principal\Domain\ValueObject\PolicyIdentifier;
use Source\Account\Principal\Domain\ValueObject\ResourceType;
use Source\Account\Principal\Domain\ValueObject\Statement;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class PolicyTest extends TestCase
{
    public function testConstructAndAccessors(): void
    {
        $identifier = new PolicyIdentifier(StrTestHelper::generateUuid());
        $createdAt = new DateTimeImmutable();
        $statement = new Statement(
            Effect::ALLOW,
            [Action::INVITATION_CREATE],
            [ResourceType::ACCOUNT],
        );

        $policy = new Policy(
            $identifier,
            'ACCOUNT_INVITATION_CREATE',
            [$statement],
            true,
            $createdAt,
        );

        $this->assertSame($identifier, $policy->policyIdentifier());
        $this->assertSame('ACCOUNT_INVITATION_CREATE', $policy->name());
        $this->assertSame([$statement], $policy->statements());
        $this->assertTrue($policy->isSystemPolicy());
        $this->assertSame($createdAt, $policy->createdAt());
    }
}
