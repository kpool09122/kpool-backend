<?php

declare(strict_types=1);

namespace Tests\Account\Delegation\Domain\Entity;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Account\Delegation\Domain\Entity\Delegation;
use Source\Account\Delegation\Domain\ValueObject\DelegationDirection;
use Source\Account\Delegation\Domain\ValueObject\DelegationStatus;
use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Tests\Helper\StrTestHelper;

class DelegationTest extends TestCase
{
    public function testApproverIsTheAffiliatedAccountThatDidNotRequest(): void
    {
        $agency = new AccountIdentifier(StrTestHelper::generateUuid());
        $talent = new AccountIdentifier(StrTestHelper::generateUuid());

        $requestedByAgency = $this->delegation($agency, $talent, $agency);
        $requestedByTalent = $this->delegation($agency, $talent, $talent);

        $this->assertSame((string) $talent, (string) $requestedByAgency->approverAccountIdentifier());
        $this->assertSame((string) $agency, (string) $requestedByTalent->approverAccountIdentifier());
    }

    private function delegation(AccountIdentifier $agency, AccountIdentifier $talent, AccountIdentifier $requestedBy): Delegation
    {
        return new Delegation(
            new DelegationIdentifier(StrTestHelper::generateUuid()),
            new AffiliationIdentifier(StrTestHelper::generateUuid()),
            $agency,
            $talent,
            $requestedBy,
            DelegationStatus::PENDING,
            (string) $requestedBy === (string) $agency ? DelegationDirection::FROM_AGENCY : DelegationDirection::FROM_TALENT,
            new DateTimeImmutable(),
            null,
            null,
        );
    }
}
