<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\DeleteAccount;

use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Domain\Exception\AccountDeletionBlockedException;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;

readonly class DeleteAccount implements DeleteAccountInterface
{
    public function __construct(
        private AccountRepositoryInterface $accountRepository,
    ) {
    }

    /**
     * @param DeleteAccountInputPort $input
     * @param DeleteAccountOutputPort $output
     * @return void
     * @throws AccountNotFoundException
     * @throws AccountDeletionBlockedException
     */
    public function process(DeleteAccountInputPort $input, DeleteAccountOutputPort $output): void
    {
        $account = $this->accountRepository->findById($input->accountIdentifier());

        if (! $account) {
            throw new AccountNotFoundException();
        }
        // TODO: 具体的な削除要件は後ほど実装

        $account->assertDeletable();

        $this->accountRepository->delete($account);

        $output->setAccount($account);
    }
}
