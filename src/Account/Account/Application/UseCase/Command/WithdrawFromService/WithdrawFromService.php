<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\WithdrawFromService;

use Source\Account\Account\Application\Service\AccountContextServiceInterface;
use Source\Account\Account\Application\Service\CurrentAccountServiceInterface;
use Source\Account\Account\Application\Service\DocumentStorageServiceInterface;
use Source\Account\Account\Domain\Event\AccountDeleting;
use Source\Account\Account\Domain\Exception\IdentityWithdrawalNotAllowedException;
use Source\Account\Account\Domain\Factory\ArchivedAccountFactoryInterface;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Account\Domain\Repository\ArchivedAccountRepositoryInterface;
use Source\Account\Principal\Domain\Entity\PrincipalGroup;
use Source\Account\Principal\Domain\Entity\Role;
use Source\Account\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Account\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Account\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\Account\Shared\Domain\ValueObject\AccountType;
use Source\Shared\Application\Service\Event\EventDispatcherInterface;
use Source\Shared\Domain\Factory\ArchivedPrincipalFactoryInterface;
use Source\Shared\Domain\Repository\ArchivedPrincipalRepositoryInterface;
use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\ArchivedPrincipalType;

readonly class WithdrawFromService implements WithdrawFromServiceInterface
{
    public function __construct(
        private AccountRepositoryInterface $accountRepository,
        private PrincipalRepositoryInterface $principalRepository,
        private PrincipalGroupRepositoryInterface $principalGroupRepository,
        private RoleRepositoryInterface $roleRepository,
        private ArchivedPrincipalRepositoryInterface $archivedPrincipalRepository,
        private ArchivedPrincipalFactoryInterface $archivedPrincipalFactory,
        private ArchivedAccountRepositoryInterface $archivedAccountRepository,
        private ArchivedAccountFactoryInterface $archivedAccountFactory,
        private DocumentStorageServiceInterface $documentStorageService,
        private CurrentAccountServiceInterface $currentAccountService,
        private AccountContextServiceInterface $accountContextService,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /** @throws IdentityWithdrawalNotAllowedException */
    public function process(WithdrawFromServiceInputPort $input, WithdrawFromServiceOutputPort $output): void
    {
        $identityIdentifier = $input->identityIdentifier();
        $principals = $this->principalRepository->findAllByIdentityIdentifier($identityIdentifier);
        if ($principals === []) {
            throw new IdentityWithdrawalNotAllowedException('An account membership is required for self-service withdrawal.');
        }
        $accountIdentifiers = [];
        foreach ($principals as $principal) {
            $accountIdentifiers[(string) $principal->accountIdentifier()] = $principal->accountIdentifier();
        }
        $accounts = [];
        foreach ($this->accountRepository->findByIds(array_values($accountIdentifiers)) as $account) {
            $accounts[(string) $account->accountIdentifier()] = $account;
        }
        $principalIdentifiers = [];
        foreach ($principals as $principal) {
            $principalIdentifiers[] = $principal->principalIdentifier();
        }
        $principalGroups = $this->principalGroupRepository->findByPrincipalIds($principalIdentifiers);
        $ownerRole = $this->roleRepository->findSystemByName(Role::OWNER);

        // Validate every actual membership before creating any Account archive.
        foreach ($principals as $principal) {
            $accountId = (string) $principal->accountIdentifier();
            $account = $accounts[$accountId] ?? null;
            if ($account === null) {
                throw new IdentityWithdrawalNotAllowedException('The account membership no longer exists.');
            }
            $isSystemOwner = $ownerRole !== null && array_any(
                $principalGroups,
                static fn (PrincipalGroup $principalGroup): bool => $principalGroup->hasMember($principal->principalIdentifier())
                    && $principalGroup->hasRole($ownerRole->roleIdentifier()),
            );
            if ($account->accountCategory() !== AccountCategory::GENERAL || $account->type() === null
                || ($account->type() === AccountType::CORPORATION && $isSystemOwner)) {
                throw new IdentityWithdrawalNotAllowedException('This account membership does not permit self-service withdrawal.');
            }
        }

        foreach ($principals as $principal) {
            $archivedPrincipal = $this->archivedPrincipalFactory->create(
                $identityIdentifier,
                ArchivedPrincipalType::ACCOUNT,
                (string) $principal->principalIdentifier(),
                $principal->accountIdentifier(),
            );
            $this->archivedPrincipalRepository->save($archivedPrincipal);
        }

        foreach ($accounts as $account) {
            if ($account->type() !== AccountType::INDIVIDUAL) {
                continue;
            }
            $archivedAccount = $this->archivedAccountFactory->create(
                $account->accountIdentifier(),
                $account->accountCategory(),
                AccountType::INDIVIDUAL,
            );
            $this->archivedAccountRepository->save($archivedAccount);
            foreach ($account->documents()->all() as $document) {
                $this->documentStorageService->delete($document->documentPath());
            }
            $this->eventDispatcher->dispatch(new AccountDeleting($account->accountIdentifier()));
            $this->accountRepository->delete($account);
        }
        $this->principalRepository->deleteByIdentityIdentifier($identityIdentifier);
        $this->accountContextService->forget($identityIdentifier);
        $this->currentAccountService->forget($identityIdentifier);
    }
}
