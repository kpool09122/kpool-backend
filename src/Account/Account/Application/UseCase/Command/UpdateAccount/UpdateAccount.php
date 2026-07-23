<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\UpdateAccount;

use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Application\Exception\AccountUpdateForbiddenException;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Account\Principal\Domain\ValueObject\AccountRole;

readonly class UpdateAccount implements UpdateAccountInterface
{
    public function __construct(
        private AccountRepositoryInterface $accountRepository,
        private PrincipalGroupRepositoryInterface $principalGroupRepository,
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

        $principalGroups = $this->principalGroupRepository->findByAccountIdAndPrincipal(
            $account->accountIdentifier(),
            new Principal($input->actorIdentityIdentifier()),
        );

        foreach ($principalGroups as $principalGroup) {
            if (in_array($principalGroup->role(), [AccountRole::OWNER, AccountRole::ADMIN], true)) {
                $account->changeName($input->accountName());
                $this->accountRepository->save($account);

                $output->setAccount($account);

                return;
            }
        }

        throw new AccountUpdateForbiddenException();
    }
}
