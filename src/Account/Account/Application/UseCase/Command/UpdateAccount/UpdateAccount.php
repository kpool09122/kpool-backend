<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\UpdateAccount;

use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Application\Exception\AccountUpdateForbiddenException;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Account\Domain\ValueObject\AccountType;
use Source\Account\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Account\Principal\Domain\ValueObject\Action;
use Source\Account\Principal\Domain\ValueObject\Resource;

readonly class UpdateAccount implements UpdateAccountInterface
{
    public function __construct(
        private AccountRepositoryInterface $accountRepository,
        private PolicyEvaluatorInterface $policyEvaluator,
    ) {
    }

    /**
     * @param UpdateAccountInputPort $input
     * @param UpdateAccountOutputPort $output
     * @return void
     * @throws AccountNotFoundException
     * @throws AccountUpdateForbiddenException
     */
    public function process(UpdateAccountInputPort $input, UpdateAccountOutputPort $output): void
    {
        $account = $this->accountRepository->findById($input->accountIdentifier());

        if (! $account) {
            throw new AccountNotFoundException();
        }

        if ($account->type() !== AccountType::CORPORATION) {
            throw new AccountUpdateForbiddenException();
        }

        if (
            (string) $input->principal()->accountIdentifier() !== (string) $account->accountIdentifier()
            || ! $this->policyEvaluator->evaluate(
                $input->principal(),
                Action::UPDATE,
                Resource::account($account->accountIdentifier(), $account->type()),
            )
        ) {
            throw new AccountUpdateForbiddenException();
        }

        $account->changeName($input->accountName());
        $account->changePhone($input->phone());
        $account->changeAddress($input->address());
        $this->accountRepository->save($account);

        $output->setAccount($account);
    }
}
