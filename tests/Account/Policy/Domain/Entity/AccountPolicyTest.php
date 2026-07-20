<?php

declare(strict_types=1);

namespace Tests\Account\Policy\Domain\Entity;

use DateTimeImmutable;
use Source\Account\Policy\Domain\Entity\AccountPolicy;
use Source\Account\Policy\Domain\ValueObject\AccountAction;
use Source\Account\Policy\Domain\ValueObject\AccountPolicyIdentifier;
use Source\Account\Policy\Domain\ValueObject\AccountResourceType;
use Source\Account\Policy\Domain\ValueObject\Effect;
use Source\Account\Policy\Domain\ValueObject\Statement;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class AccountPolicyTest extends TestCase
{
    public function testConstructAndAccessors(): void
    {
        $identifier = new AccountPolicyIdentifier(StrTestHelper::generateUuid());
        $createdAt = new DateTimeImmutable();
        $statement = new Statement(
            Effect::ALLOW,
            [AccountAction::INVITATION_CREATE],
            [AccountResourceType::ACCOUNT],
        );

        $policy = new AccountPolicy(
            $identifier,
            'ACCOUNT_INVITATION_CREATE',
            [$statement],
            true,
            $createdAt,
        );

        $this->assertSame($identifier, $policy->accountPolicyIdentifier());
        $this->assertSame('ACCOUNT_INVITATION_CREATE', $policy->name());
        $this->assertSame([$statement], $policy->statements());
        $this->assertTrue($policy->isSystemPolicy());
        $this->assertSame($createdAt, $policy->createdAt());
    }
}
