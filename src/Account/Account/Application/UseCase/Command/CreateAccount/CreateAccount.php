<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\CreateAccount;

use Source\Account\Account\Domain\Event\AccountCreationConflicted;
use Source\Account\Account\Domain\Event\AccountCreated;
use Source\Account\Account\Domain\Factory\AccountFactoryInterface;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\IdentityGroup\Domain\Factory\IdentityGroupFactoryInterface;
use Source\Account\IdentityGroup\Domain\Repository\IdentityGroupRepositoryInterface;
use Source\Account\IdentityGroup\Domain\ValueObject\AccountRole;
use Source\Shared\Application\Service\Event\EventDispatcherInterface;

readonly class CreateAccount implements CreateAccountInterface
{
    private const string DEFAULT_IDENTITY_GROUP_NAME = 'Owners';

    public function __construct(
        private AccountRepositoryInterface $accountRepository,
        private AccountFactoryInterface $accountFactory,
        private IdentityGroupFactoryInterface $identityGroupFactory,
        private IdentityGroupRepositoryInterface $identityGroupRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * @param CreateAccountInputPort $input
     * @param CreateAccountOutputPort $output
     * @return void
     */
    public function process(CreateAccountInputPort $input, CreateAccountOutputPort $output): void
    {
        $account = $this->accountRepository->findByEmail($input->email());

        if ($account) {
            $this->eventDispatcher->dispatch(new AccountCreationConflicted(
                email: $input->email(),
                language: $input->language(),
            ));

            return;
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

        $this->eventDispatcher->dispatch(new AccountCreated(
            accountIdentifier: $account->accountIdentifier(),
            email: $account->email(),
            identityIdentifier: $input->identityIdentifier(),
            language: $input->language(),
        ));

        $output->setAccount($account);
    }
}
