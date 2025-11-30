<?php

declare(strict_types=1);

namespace Source\Account\Application\UseCase\Command\WithdrawFromMembership;

use Source\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Domain\Entity\Account;
use Source\Account\Domain\Exception\AccountMembershipNotFoundException;
use Source\Account\Domain\Exception\DisallowedToWithdrawByOwnerException;
use Source\Account\Domain\Repository\AccountRepositoryInterface;

readonly class WithdrawFromMembership implements WithdrawFromMembershipInterface
{
    public function __construct(
        private AccountRepositoryInterface $accountRepository,
    ) {
    }

    /**
     * @param WithdrawFromMembershipInputPort $input
     * @return Account
     * @throws AccountNotFoundException
     * @throws AccountMembershipNotFoundException
     * @throws DisallowedToWithdrawByOwnerException
     */
    public function process(WithdrawFromMembershipInputPort $input): Account
    {
        $account = $this->accountRepository->findById($input->accountIdentifier());

        if (! $account) {
            throw new AccountNotFoundException();
        }

        $account->detachMember($input->accountMembership());

        $this->accountRepository->save($account);

        return $account;
    }
}
