<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\CreateAccount;

use Source\Account\Account\Application\Exception\AccountAlreadyExistsException;
use Source\Account\Account\Domain\Entity\Account;
use Source\Account\Account\Domain\Factory\AccountFactoryInterface;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\IdentityGroup\Domain\Factory\IdentityGroupFactoryInterface;
use Source\Account\IdentityGroup\Domain\Repository\IdentityGroupRepositoryInterface;
use Source\Account\IdentityGroup\Domain\ValueObject\AccountRole;

readonly class CreateAccount implements CreateAccountInterface
{
    private const string DEFAULT_IDENTITY_GROUP_NAME = 'Owners';

    public function __construct(
        private AccountRepositoryInterface $accountRepository,
        private AccountFactoryInterface $accountFactory,
        private IdentityGroupFactoryInterface $identityGroupFactory,
        private IdentityGroupRepositoryInterface $identityGroupRepository,
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
        );

        $this->accountRepository->save($account);

        $identityGroup = $this->identityGroupFactory->create(
            $account->accountIdentifier(),
            self::DEFAULT_IDENTITY_GROUP_NAME,
            AccountRole::OWNER,
            true,
        );

        if ($input->identityIdentifier() !== null) {
            $identityGroup->addMember($input->identityIdentifier());
        }

        $this->identityGroupRepository->save($identityGroup);

        return $account;
    }
}
