<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\WithdrawFromService;

use Source\Account\Account\Application\Service\AccountContextServiceInterface;
use Source\Account\Account\Application\Service\CurrentAccountServiceInterface;
use Source\Account\Account\Application\Service\DocumentStorageServiceInterface;
use Source\Account\Account\Application\Service\WithdrawalEligibilityServiceInterface;
use Source\Account\Account\Domain\Event\AccountDeleting;
use Source\Account\Account\Domain\Exception\IdentityWithdrawalNotAllowedException;
use Source\Account\Account\Domain\Factory\ArchivedAccountFactoryInterface;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Account\Domain\Repository\ArchivedAccountRepositoryInterface;
use Source\Account\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Account\Shared\Domain\ValueObject\AccountType;
use Source\Shared\Application\Service\Event\EventDispatcherInterface;
use Source\Shared\Domain\Factory\ArchivedPrincipalFactoryInterface;
use Source\Shared\Domain\Repository\ArchivedPrincipalRepositoryInterface;
use Source\Shared\Domain\ValueObject\ArchivedPrincipalType;

readonly class WithdrawFromService implements WithdrawFromServiceInterface
{
    public function __construct(
        private AccountRepositoryInterface $accountRepository,
        private PrincipalRepositoryInterface $principalRepository,
        private WithdrawalEligibilityServiceInterface $withdrawalEligibilityService,
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
        $memberships = $this->withdrawalEligibilityService->resolve($identityIdentifier);
        $principals = $memberships->principals;
        $accounts = $memberships->accounts;

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
        $this->accountContextService->forgetByIdentityIdentifier($identityIdentifier);
        $this->currentAccountService->forget($identityIdentifier);
    }
}
