<?php

declare(strict_types=1);

namespace Source\Account\Application\UseCase\Command\WithdrawFromMembership;

use Source\Account\Domain\Entity\AccountMembership;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface WithdrawFromMembershipInputPort
{
    public function accountIdentifier(): AccountIdentifier;

    public function accountMembership(): AccountMembership;
}
