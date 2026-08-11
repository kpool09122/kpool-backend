<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\CreateAccount;

use RuntimeException;
use Source\Account\Account\Domain\Event\AccountCreated;
use Source\Account\Account\Domain\Event\AccountCreationConflicted;
use Source\Account\Account\Domain\Factory\AccountFactoryInterface;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Principal\Domain\Entity\Role;
use Source\Account\Principal\Domain\Factory\PrincipalFactoryInterface;
use Source\Account\Principal\Domain\Factory\PrincipalGroupFactoryInterface;
use Source\Account\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Account\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Account\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\Shared\Application\Service\Event\EventDispatcherInterface;

readonly class CreateAccount implements CreateAccountInterface
{
    private const string DEFAULT_GROUP_NAME = 'Default';
    private const string OWNER_GROUP_NAME = 'Owners';

    public function __construct(
        private AccountRepositoryInterface $accountRepository,
        private AccountFactoryInterface $accountFactory,
        private PrincipalFactoryInterface $principalFactory,
        private PrincipalRepositoryInterface $principalRepository,
        private PrincipalGroupFactoryInterface $principalGroupFactory,
        private PrincipalGroupRepositoryInterface $principalGroupRepository,
        private RoleRepositoryInterface $roleRepository,
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
            $input->phone(),
            $input->address(),
        );

        $this->accountRepository->save($account);

        $defaultPrincipalGroup = $this->principalGroupFactory->create(
            $account->accountIdentifier(),
            self::DEFAULT_GROUP_NAME,
            true,
        );

        $ownerPrincipalGroup = $this->principalGroupFactory->create(
            $account->accountIdentifier(),
            self::OWNER_GROUP_NAME,
            false,
        );

        $ownerRole = $this->roleRepository->findByName(Role::OWNER);
        if ($ownerRole === null) {
            throw new RuntimeException('Owner account role is not found.');
        }
        $ownerPrincipalGroup->addRole($ownerRole->roleIdentifier());

        if ($input->identityIdentifier() !== null) {
            $principal = $this->principalFactory->create(
                $input->identityIdentifier(),
                $account->accountIdentifier(),
            );
            $this->principalRepository->save($principal);
            $ownerPrincipalGroup->addMember($principal->principalIdentifier());
        }

        $this->principalGroupRepository->save($defaultPrincipalGroup);
        $this->principalGroupRepository->save($ownerPrincipalGroup);

        $this->eventDispatcher->dispatch(new AccountCreated(
            accountIdentifier: $account->accountIdentifier(),
            email: $account->email(),
            identityIdentifier: $input->identityIdentifier(),
            language: $input->language(),
        ));

        $output->setAccount($account);
    }
}
