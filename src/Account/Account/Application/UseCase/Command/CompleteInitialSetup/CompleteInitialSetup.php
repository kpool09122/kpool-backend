<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\CompleteInitialSetup;

use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Application\Repository\AccountSetupRepositoryInterface;

readonly class CompleteInitialSetup implements CompleteInitialSetupInterface
{
    public function __construct(private AccountSetupRepositoryInterface $accountSetupRepository)
    {
    }

    public function process(CompleteInitialSetupInputPort $input): void
    {
        $account = $this->accountSetupRepository->findByIdForUpdate($input->accountIdentifier());
        if ($account === null) {
            throw new AccountNotFoundException();
        }

        $account->completeInitialSetup($input->accountType());
        $this->accountSetupRepository->save($account);
    }
}
