<?php

declare(strict_types=1);

namespace Source\Account\Application\UseCase\Command\WithdrawFromMembership;

use Source\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Domain\Entity\Account;
use Source\Account\Domain\Exception\AccountMembershipNotFoundException;
use Source\Account\Domain\Exception\DisallowedToWithdrawByOwnerException;

interface WithdrawFromMembershipInterface
{
    /**
     * @param WithdrawFromMembershipInputPort $input
     * @return Account
     * @throws AccountNotFoundException
     * @throws AccountMembershipNotFoundException
     * @throws DisallowedToWithdrawByOwnerException
     */
    public function process(WithdrawFromMembershipInputPort $input): Account;
}
