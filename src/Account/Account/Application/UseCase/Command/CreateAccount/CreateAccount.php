<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\CreateAccount;

use Source\Account\Account\Domain\Event\AccountCreated;
use Source\Account\Account\Domain\Event\AccountCreationConflicted;
use Source\Account\Account\Domain\Factory\AccountFactoryInterface;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Principal\Domain\Factory\PrincipalGroupFactoryInterface;
use Source\Account\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Account\Principal\Domain\ValueObject\AccountRole;
use Source\Shared\Application\Service\Event\EventDispatcherInterface;

readonly class CreateAccount implements CreateAccountInterface
{
    private const string DEFAULT_IDENTITY_GROUP_NAME = 'Owners';

    public function __construct(
        private AccountRepositoryInterface $accountRepository,
        private AccountFactoryInterface $accountFactory,
        private PrincipalGroupFactoryInterface $principalGroupFactory,
        private PrincipalGroupRepositoryInterface $principalGroupRepository,
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

        $principalGroup = $this->principalGroupFactory->create(
            $account->accountIdentifier(),
            self::DEFAULT_IDENTITY_GROUP_NAME,
            AccountRole::OWNER,
            true,
        );

        if ($input->identityIdentifier() !== null) {
            $principalGroup->addMember(new Principal($input->identityIdentifier()));
        }

        $this->principalGroupRepository->save($principalGroup);

        $this->eventDispatcher->dispatch(new AccountCreated(
            accountIdentifier: $account->accountIdentifier(),
            email: $account->email(),
            identityIdentifier: $input->identityIdentifier(),
            language: $input->language(),
        ));

        $output->setAccount($account);
    }
}
