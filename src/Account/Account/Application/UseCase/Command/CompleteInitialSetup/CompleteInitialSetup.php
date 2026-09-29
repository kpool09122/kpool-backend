<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\CompleteInitialSetup;

use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;

readonly class CompleteInitialSetup implements CompleteInitialSetupInterface
{
    public function __construct(private AccountRepositoryInterface $accountRepository)
    {
    }

    public function process(CompleteInitialSetupInputPort $input): void
    {
        $account = $this->accountRepository->findById($input->accountIdentifier());
        if ($account === null) {
            throw new AccountNotFoundException();
        }

        $account->completeInitialSetup($input->accountType());
        $this->accountRepository->save($account);
    }
}
