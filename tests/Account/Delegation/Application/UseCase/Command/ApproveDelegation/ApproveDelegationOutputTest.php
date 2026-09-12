<?php

declare(strict_types=1);

namespace Tests\Account\Delegation\Application\UseCase\Command\ApproveDelegation;

use DateTimeImmutable;
use Source\Account\Delegation\Application\UseCase\Command\ApproveDelegation\ApproveDelegationOutput;
use Source\Account\Delegation\Domain\Entity\AccountDelegation;
use Source\Account\Delegation\Domain\ValueObject\DelegationDirection;
use Source\Account\Delegation\Domain\ValueObject\DelegationStatus;
use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ApproveDelegationOutputTest extends TestCase
{
    public function testSerializesAccountDelegationShape(): void
    {
        $delegation = new AccountDelegation(new DelegationIdentifier(StrTestHelper::generateUuid()), new AffiliationIdentifier(StrTestHelper::generateUuid()), new AccountIdentifier(StrTestHelper::generateUuid()), new AccountIdentifier(StrTestHelper::generateUuid()), new AccountIdentifier(StrTestHelper::generateUuid()), DelegationStatus::APPROVED, DelegationDirection::FROM_AGENCY, new DateTimeImmutable(), new DateTimeImmutable(), null);
        $output = new ApproveDelegationOutput();
        $output->setDelegation($delegation);
        $array = $output->toArray();
        $this->assertSame('approved', $array['status']);
        $this->assertArrayHasKey('delegateAccountIdentifier', $array);
        $this->assertArrayNotHasKey('delegateIdentifier', $array);
    }
}
