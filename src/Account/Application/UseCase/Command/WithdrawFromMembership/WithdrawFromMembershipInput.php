<?php

declare(strict_types=1);

namespace Source\Account\Application\UseCase\Command\WithdrawFromMembership;

use Source\Account\Domain\Entity\AccountMembership;
use Source\Account\Domain\ValueObject\AccountIdentifier;

readonly class WithdrawFromMembershipInput implements WithdrawFromMembershipInputPort
{
    public function __construct(
        private AccountIdentifier $accountIdentifier,
        private AccountMembership $accountMembership,
    ) {
    }

    public function accountIdentifier(): AccountIdentifier
    {
        return $this->accountIdentifier;
    }

    public function accountMembership(): AccountMembership
    {
        return $this->accountMembership;
    }
}
