<?php

declare(strict_types=1);

namespace Source\Identity\Infrastructure\Service;

use Application\Http\Context\AccountResolver;
use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Application\Service\CurrentAccountServiceInterface;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Account\Domain\ValueObject\AccountStatus;
use Source\Account\Delegation\Domain\Repository\DelegationRepositoryInterface;
use Source\Account\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Account\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Account\Principal\Domain\ValueObject\Action;
use Source\Account\Principal\Domain\ValueObject\PrincipalStatus;
use Source\Account\Principal\Domain\ValueObject\Resource;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Identity\Application\Exception\AuthorizationAccountUnavailableException;
use Source\Identity\Application\Service\AuthorizationAccount;
use Source\Identity\Application\Service\AuthorizationAccountServiceInterface;
use Source\Identity\Domain\ValueObject\AccountPrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class AuthorizationAccountService implements AuthorizationAccountServiceInterface
{
    public function __construct(
        private CurrentAccountServiceInterface $currentAccountService,
        private AccountResolver $accountResolver,
        private AccountRepositoryInterface $accountRepository,
        private DelegationRepositoryInterface $delegationRepository,
        private PrincipalRepositoryInterface $principalRepository,
        private PolicyEvaluatorInterface $policyEvaluator,
    ) {
    }

    public function resolveOriginalAccount(IdentityIdentifier $identityIdentifier): AuthorizationAccount
    {
        $current = $this->currentAccountService->find($identityIdentifier);
        if ($current === null) {
            try {
                $principal = $this->accountResolver->resolveInitialPrincipal($identityIdentifier);
            } catch (AccountNotFoundException $exception) {
                throw new AuthorizationAccountUnavailableException($exception);
            }
        } else {
            if ((string) $current->originalIdentityIdentifier !== (string) $identityIdentifier) {
                throw new AuthorizationAccountUnavailableException();
            }
            $principal = $this->principalRepository->findById($current->originalPrincipalIdentifier);
        }
        if ($principal === null || $principal->status() !== PrincipalStatus::ACTIVE
            || (string) $principal->identityIdentifier() !== (string) $identityIdentifier
            || ($current !== null && (string) $principal->accountIdentifier() !== (string) $current->originalAccountIdentifier)) {
            throw new AuthorizationAccountUnavailableException();
        }
        $original = $this->accountRepository->findById($principal->accountIdentifier());
        if ($original === null || $original->status() !== AccountStatus::ACTIVE) {
            throw new AuthorizationAccountUnavailableException();
        }

        return new AuthorizationAccount(
            $identityIdentifier,
            $original->accountIdentifier(),
            new AccountPrincipalIdentifier((string) $principal->principalIdentifier()),
            (string) $original->name(),
        );
    }

    public function allowsDelegatedTarget(AuthorizationAccount $originalAccount, AccountIdentifier $accountIdentifier, DelegationIdentifier $delegationIdentifier): bool
    {
        $delegation = $this->delegationRepository->findById($delegationIdentifier);
        if ($delegation === null || ! $delegation->isApproved()
            || (string) $delegation->delegateAccountIdentifier() !== (string) $originalAccount->accountIdentifier
            || (string) $delegation->delegatorAccountIdentifier() !== (string) $accountIdentifier) {
            return false;
        }
        $account = $this->accountRepository->findById($accountIdentifier);

        if ($account?->status() !== AccountStatus::ACTIVE) {
            return false;
        }

        $effective = $this->principalRepository->findByIdentityIdentifierAndAccountIdentifier($originalAccount->identityIdentifier, $accountIdentifier);
        if ($effective !== null && $effective->status() !== PrincipalStatus::ACTIVE) {
            return false;
        }

        $principal = $this->principalRepository->findById(new PrincipalIdentifier((string) $originalAccount->principalIdentifier));

        return $principal !== null
            && $principal->status() === PrincipalStatus::ACTIVE
            && (string) $principal->identityIdentifier() === (string) $originalAccount->identityIdentifier
            && (string) $principal->accountIdentifier() === (string) $originalAccount->accountIdentifier
            && $this->policyEvaluator->evaluate(
                $principal,
                Action::DELEGATION_ACCOUNT_SWITCH,
                Resource::delegationAccount($originalAccount->accountIdentifier, $delegationIdentifier, $accountIdentifier),
            );
    }
}
