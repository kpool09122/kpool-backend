<?php

declare(strict_types=1);

namespace Source\Account\Application\UseCase\Command\CreateAccount;

use Source\Account\Application\Exception\AccountAlreadyExistsException;
use Source\Account\Domain\Entity\Account;
use Source\Account\Domain\Factory\AccountFactoryInterface;
use Source\Account\Domain\Repository\AccountRepositoryInterface;

readonly class CreateAccount implements CreateAccountInterface
{
    public function __construct(
        private AccountRepositoryInterface $accountRepository,
        private AccountFactoryInterface $accountFactory,
    ) {
    }

    /**
     * @param CreateAccountInputPort $input
     * @return Account
     * @throws AccountAlreadyExistsException
     */
    public function process(CreateAccountInputPort $input): Account
    {
        $account = $this->accountRepository->findByEmail($input->email());

        if ($account) {
            throw new AccountAlreadyExistsException('Account already exists.');
        }

        $account = $this->accountFactory->create(
            $input->email(),
            $input->accountType(),
            $input->accountName(),
            $input->contractInfo(),
        );

        $this->accountRepository->save($account);

        return $account;
    }
}
