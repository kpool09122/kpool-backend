<?php

declare(strict_types=1);

namespace Application\Http\Context;

use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Application\Service\CurrentAccount;
use Source\Account\Account\Application\Service\CurrentAccountServiceInterface;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Account\Domain\ValueObject\AccountStatus;
use Source\Account\Delegation\Application\Exception\DelegationUnavailableException;
use Source\Account\Delegation\Domain\Repository\DelegationRepositoryInterface;
use Source\Account\Principal\Domain\Entity\Policy;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Principal\Domain\Repository\PolicyRepositoryInterface;
use Source\Account\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Account\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Account\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\Account\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Account\Principal\Domain\ValueObject\Action;
use Source\Account\Principal\Domain\ValueObject\Condition;
use Source\Account\Principal\Domain\ValueObject\ConditionClause;
use Source\Account\Principal\Domain\ValueObject\PrincipalStatus;
use Source\Account\Principal\Domain\ValueObject\Resource;
use Source\Account\Principal\Domain\ValueObject\ResourceType;
use Source\Account\Principal\Domain\ValueObject\RoleIdentifier;
use Source\Account\Principal\Domain\ValueObject\Statement;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Identity\Application\Service\ApiAuthenticationContext;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class AccountResolver
{
    public function __construct(
        private CurrentAccountServiceInterface $currentAccountService,
        private AccountRepositoryInterface $accountRepository,
        private PrincipalRepositoryInterface $principalRepository,
        private DelegationRepositoryInterface $delegationRepository,
        private PrincipalGroupRepositoryInterface $principalGroupRepository,
        private RoleRepositoryInterface $roleRepository,
        private PolicyRepositoryInterface $policyRepository,
        private PolicyEvaluatorInterface $policyEvaluator,
    ) {
    }

    /** @throws AccountNotFoundException|DelegationUnavailableException */
    public function resolve(IdentityIdentifier $identityIdentifier): AccountContext
    {
        $currentAccount = $this->currentAccountService->find($identityIdentifier)
            ?? $this->initialCurrentAccount($identityIdentifier);

        if ((string) $currentAccount->originalIdentityIdentifier !== (string) $identityIdentifier) {
            throw new AccountNotFoundException('Current account does not belong to the authenticated identity.');
        }

        $principal = $this->principalRepository->findById($currentAccount->effectivePrincipalIdentifier);
        if ($principal === null
            || (string) $principal->identityIdentifier() !== (string) $identityIdentifier
            || (string) $principal->accountIdentifier() !== (string) $currentAccount->effectiveAccountIdentifier) {
            throw new AccountNotFoundException('Selected account principal was not found.');
        }

        $this->assertDelegationIsActive($currentAccount);
        $authorization = request()->attributes->get(ApiAuthenticationContext::class);
        if ($authorization instanceof ApiAuthenticationContext) {
            $originalPrincipal = $this->resolveInitialPrincipal($identityIdentifier);
            if ((string) $currentAccount->originalAccountIdentifier !== (string) $originalPrincipal->accountIdentifier()
                || (string) $currentAccount->originalPrincipalIdentifier !== (string) $originalPrincipal->principalIdentifier()
                || $originalPrincipal->status() !== PrincipalStatus::ACTIVE
                || $principal->status() !== PrincipalStatus::ACTIVE) {
                throw new AccountNotFoundException('Selected account is not authorized.');
            }
            if ($currentAccount->delegationIdentifier !== null && ! $this->policyEvaluator->evaluate(
                $originalPrincipal,
                Action::DELEGATION_ACCOUNT_SWITCH,
                Resource::delegationAccount($originalPrincipal->accountIdentifier(), $currentAccount->delegationIdentifier, $currentAccount->effectiveAccountIdentifier),
            )) {
                throw new AccountNotFoundException('Delegation permission is no longer available.');
            }
        }

        $account = $this->accountRepository->findById($currentAccount->effectiveAccountIdentifier);
        if ($account === null) {
            throw new AccountNotFoundException('Selected account was not found.');
        }

        $originalAccountStatus = $account->status();
        if ((string) $currentAccount->originalAccountIdentifier !== (string) $currentAccount->effectiveAccountIdentifier) {
            $originalAccount = $this->accountRepository->findById($currentAccount->originalAccountIdentifier);
            if ($originalAccount === null) {
                throw new AccountNotFoundException('Original account was not found.');
            }
            $originalAccountStatus = $originalAccount->status();
        }

        if ($authorization instanceof ApiAuthenticationContext
            && ($account->status() !== AccountStatus::ACTIVE || $originalAccountStatus !== AccountStatus::ACTIVE)) {
            throw new AccountNotFoundException('The account is not active.');
        }

        $principalGroups = $this->principalGroupRepository->findByAccountIdAndPrincipal(
            $principal->accountIdentifier(),
            $principal->principalIdentifier(),
        );
        $roleIdentifiers = [];
        foreach ($principalGroups as $principalGroup) {
            foreach ($principalGroup->roles() as $roleIdentifier) {
                $roleIdentifiers[(string) $roleIdentifier] = $roleIdentifier;
            }
        }

        return new AccountContext(
            principal: $principal,
            accountType: $account->type(),
            accountStatus: $account->status(),
            accountCategory: $account->accountCategory(),
            accountPolicies: $this->effectivePolicies(array_values($roleIdentifiers)),
            originalIdentityIdentifier: $currentAccount->originalIdentityIdentifier,
            originalAccountIdentifier: $currentAccount->originalAccountIdentifier,
            originalPrincipalIdentifier: $currentAccount->originalPrincipalIdentifier,
            delegationIdentifier: $currentAccount->delegationIdentifier,
            originalAccountStatus: $originalAccountStatus,
        );
    }

    /** @throws AccountNotFoundException */
    public function resolveOriginalPrincipalIdentifier(IdentityIdentifier $identityIdentifier): PrincipalIdentifier
    {
        if (request()->attributes->get(ApiAuthenticationContext::class) instanceof ApiAuthenticationContext) {
            return $this->resolveInitialPrincipal($identityIdentifier)->principalIdentifier();
        }

        return app(AccountContext::class)->originalPrincipalIdentifier();
    }

    /** @throws AccountNotFoundException */
    public function resolveInitialPrincipal(IdentityIdentifier $identityIdentifier): Principal
    {
        $authorization = request()->attributes->get(ApiAuthenticationContext::class);
        if ($authorization instanceof ApiAuthenticationContext) {
            $principal = $this->principalRepository->findById(new PrincipalIdentifier((string) $authorization->originalPrincipalIdentifier()));
            if ($principal === null
                || (string) $authorization->identityIdentifier() !== (string) $identityIdentifier
                || (string) $principal->identityIdentifier() !== (string) $identityIdentifier
                || (string) $principal->accountIdentifier() !== (string) $authorization->originalAccountIdentifier()) {
                throw new AccountNotFoundException('The original principal is not available.');
            }

            return $principal;
        }
        $principals = $this->principalRepository->findAllByIdentityIdentifier($identityIdentifier);
        $principal = count($principals) === 1
            ? $principals[0]
            : $this->originalPrincipalFromDelegatedPrincipals($principals);

        if ($principal === null) {
            throw new AccountNotFoundException('Current account is not selected.');
        }

        return $principal;
    }

    private function initialCurrentAccount(IdentityIdentifier $identityIdentifier): CurrentAccount
    {
        $principal = $this->resolveInitialPrincipal($identityIdentifier);
        $currentAccount = new CurrentAccount(
            originalIdentityIdentifier: $identityIdentifier,
            originalAccountIdentifier: $principal->accountIdentifier(),
            originalPrincipalIdentifier: $principal->principalIdentifier(),
            effectiveAccountIdentifier: $principal->accountIdentifier(),
            effectivePrincipalIdentifier: $principal->principalIdentifier(),
            delegationIdentifier: null,
        );
        $this->currentAccountService->save($currentAccount);

        return $currentAccount;
    }

    /**
     * @param array<int, Principal> $principals
     */
    private function originalPrincipalFromDelegatedPrincipals(array $principals): ?Principal
    {
        if ($principals === []) {
            return null;
        }

        $principalsByAccountId = [];
        foreach ($principals as $principal) {
            $principalsByAccountId[(string) $principal->accountIdentifier()] = $principal;
        }

        $delegations = $this->delegationRepository->findApprovedBetweenAccountIds(
            array_map(
                static fn (Principal $principal) => $principal->accountIdentifier(),
                $principals,
            ),
        );
        $delegatedAccountIds = [];
        foreach ($delegations as $delegation) {
            $delegateAccountId = (string) $delegation->delegateAccountIdentifier();
            $delegatorAccountId = (string) $delegation->delegatorAccountIdentifier();

            if (isset($principalsByAccountId[$delegateAccountId], $principalsByAccountId[$delegatorAccountId])) {
                $delegatedAccountIds[$delegatorAccountId] = true;
            }
        }

        $originalPrincipalCandidates = array_values(array_filter(
            $principals,
            static fn (Principal $principal): bool => ! isset($delegatedAccountIds[(string) $principal->accountIdentifier()]),
        ));

        return count($originalPrincipalCandidates) === 1 ? $originalPrincipalCandidates[0] : null;
    }

    private function assertDelegationIsActive(CurrentAccount $currentAccount): void
    {
        if ($currentAccount->delegationIdentifier === null) {
            if ((string) $currentAccount->originalAccountIdentifier !== (string) $currentAccount->effectiveAccountIdentifier
                || (string) $currentAccount->originalPrincipalIdentifier !== (string) $currentAccount->effectivePrincipalIdentifier) {
                throw new AccountNotFoundException('Non-delegated current account is inconsistent.');
            }

            return;
        }

        $delegation = $this->delegationRepository->findById($currentAccount->delegationIdentifier);
        if ($delegation === null || ! $delegation->isApproved()
            || (string) $delegation->delegateAccountIdentifier() !== (string) $currentAccount->originalAccountIdentifier
            || (string) $delegation->delegatorAccountIdentifier() !== (string) $currentAccount->effectiveAccountIdentifier) {
            if (request()->attributes->get(ApiAuthenticationContext::class) instanceof ApiAuthenticationContext) {
                throw new DelegationUnavailableException('Delegated account context is no longer active.');
            }
            $this->currentAccountService->save(new CurrentAccount(
                originalIdentityIdentifier: $currentAccount->originalIdentityIdentifier,
                originalAccountIdentifier: $currentAccount->originalAccountIdentifier,
                originalPrincipalIdentifier: $currentAccount->originalPrincipalIdentifier,
                effectiveAccountIdentifier: $currentAccount->originalAccountIdentifier,
                effectivePrincipalIdentifier: $currentAccount->originalPrincipalIdentifier,
                delegationIdentifier: null,
            ));

            throw new DelegationUnavailableException('Delegated account context is no longer active.');
        }
    }

    /**
     * @param RoleIdentifier[] $roleIdentifiers
     * @return array<int, array<string, mixed>>
     */
    private function effectivePolicies(array $roleIdentifiers): array
    {
        if (empty($roleIdentifiers)) {
            return [];
        }

        $roles = $this->roleRepository->findByIds($roleIdentifiers);
        $policyIdentifiers = [];
        foreach ($roles as $role) {
            foreach ($role->policies() as $policyIdentifier) {
                $policyIdentifiers[(string) $policyIdentifier] = $policyIdentifier;
            }
        }

        $policies = $this->policyRepository->findByIds(array_values($policyIdentifiers));
        ksort($policies);

        return array_map($this->toPolicyArray(...), array_values($policies));
    }

    /**
     * @return array{policyIdentifier: string, name: string, isSystemPolicy: bool, statements: array<int, array<string, mixed>>}
     */
    private function toPolicyArray(Policy $policy): array
    {
        return [
            'policyIdentifier' => (string) $policy->policyIdentifier(),
            'name' => $policy->name(),
            'isSystemPolicy' => $policy->isSystemPolicy(),
            'statements' => array_map($this->toStatementArray(...), $policy->statements()),
        ];
    }

    /**
     * @return array{effect: string, actions: array<int, string>, resourceTypes: array<int, string>, condition: array{clauses: array<int, array{field: string, operator: string, value: string|bool|list<string>}>}|null}
     */
    private function toStatementArray(Statement $statement): array
    {
        return [
            'effect' => $statement->effect()->value,
            'actions' => array_map(static fn (Action $action): string => $action->value, $statement->actions()),
            'resourceTypes' => array_map(static fn (ResourceType $resourceType): string => $resourceType->value, $statement->resourceTypes()),
            'condition' => $this->toConditionArray($statement->condition()),
        ];
    }

    /**
     * @return array{clauses: array<int, array{field: string, operator: string, value: string|bool|list<string>}>}|null
     */
    private function toConditionArray(?Condition $condition): ?array
    {
        if ($condition === null) {
            return null;
        }

        return [
            'clauses' => array_map($this->toConditionClauseArray(...), $condition->clauses()),
        ];
    }

    /**
     * @return array{field: string, operator: string, value: string|bool|list<string>}
     */
    private function toConditionClauseArray(ConditionClause $clause): array
    {
        return [
            'field' => $clause->key()->value,
            'operator' => $clause->operator()->value,
            'value' => $clause->value(),
        ];
    }
}
