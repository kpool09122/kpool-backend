<?php

declare(strict_types=1);

namespace Source\Account\Principal\Infrastructure\Service;

use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Account\Domain\ValueObject\AccountStatus;
use Source\Account\Delegation\Domain\Repository\DelegationRepositoryInterface;
use Source\Account\Principal\Application\Service\PrincipalReachabilityInterface;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Principal\Domain\Entity\PrincipalGroup;
use Source\Account\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Account\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Account\Principal\Domain\ValueObject\Action;
use Source\Account\Principal\Domain\ValueObject\PolicyEvaluation;
use Source\Account\Principal\Domain\ValueObject\PrincipalStatus;
use Source\Account\Principal\Domain\ValueObject\Resource;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class PrincipalReachability implements PrincipalReachabilityInterface
{
    public function __construct(
        private PrincipalRepositoryInterface $principalRepository,
        private AccountRepositoryInterface $accountRepository,
        private DelegationRepositoryInterface $delegationRepository,
        private PolicyEvaluatorInterface $policyEvaluator,
    ) {
    }

    /** @param array<string, Principal> $principalsById
     * @param PrincipalGroup[]|null $principalGroups */
    public function canAccess(IdentityIdentifier $identityIdentifier, AccountIdentifier $accountIdentifier, array $principalsById = [], ?array $principalGroups = null, ?AccountIdentifier $principalGroupsAccountIdentifier = null): bool
    {
        $relatedPrincipals = $this->principalRepository->findAllByIdentityIdentifier($identityIdentifier);
        $targets = array_filter($relatedPrincipals, static fn (Principal $principal): bool => (string) $principal->accountIdentifier() === (string) $accountIdentifier);

        return $this->resolveReachable($targets, $relatedPrincipals, $principalsById, $principalGroups, $principalGroupsAccountIdentifier) !== [];
    }

    /**
     * @param Principal[] $principals
     * @param array<string, Principal> $principalsById
     * @param PrincipalGroup[]|null $principalGroups
     * @return array<string, Principal>
     */
    public function filterReachable(array $principals, array $principalsById = [], ?array $principalGroups = null, ?AccountIdentifier $principalGroupsAccountIdentifier = null): array
    {
        if ($principals === []) {
            return [];
        }
        $identityIdentifiers = [];
        foreach ($principals as $principal) {
            $identityIdentifiers[(string) $principal->identityIdentifier()] = $principal->identityIdentifier();
        }
        $relatedPrincipals = $this->principalRepository->findAllByIdentityIdentifiers(array_values($identityIdentifiers));

        return $this->resolveReachable($principals, $relatedPrincipals, $principalsById, $principalGroups, $principalGroupsAccountIdentifier);
    }

    /**
     * @param Principal[] $targets
     * @param Principal[] $relatedPrincipals
     * @param array<string, Principal> $principalsById
     * @param PrincipalGroup[]|null $principalGroups
     * @return array<string, Principal>
     */
    private function resolveReachable(array $targets, array $relatedPrincipals, array $principalsById, ?array $principalGroups, ?AccountIdentifier $principalGroupsAccountIdentifier): array
    {
        if ($targets === []) {
            return [];
        }
        $principalsByIdentity = [];
        $accountIdentifiers = [];
        foreach ($relatedPrincipals as $principal) {
            $principal = $principalsById[(string) $principal->principalIdentifier()] ?? $principal;
            $principalsByIdentity[(string) $principal->identityIdentifier()][(string) $principal->accountIdentifier()] = $principal;
            $accountIdentifiers[(string) $principal->accountIdentifier()] = $principal->accountIdentifier();
        }
        $activeAccounts = [];
        foreach ($this->accountRepository->findByIds(array_values($accountIdentifiers)) as $account) {
            if ($account->status() === AccountStatus::ACTIVE) {
                $activeAccounts[(string) $account->accountIdentifier()] = true;
            }
        }
        $delegations = $this->delegationRepository->findApprovedBetweenAccountIds(array_values($accountIdentifiers));
        $reachable = [];
        $evaluations = [];
        $delegatedTargets = [];
        foreach ($targets as $target) {
            $principals = $principalsByIdentity[(string) $target->identityIdentifier()] ?? [];
            $target = $principals[(string) $target->accountIdentifier()] ?? null;
            if ($target === null || $target->status() !== PrincipalStatus::ACTIVE || ! isset($activeAccounts[(string) $target->accountIdentifier()])) {
                continue;
            }
            $delegatedAccounts = [];
            $identityDelegations = [];
            foreach ($delegations as $delegation) {
                // The batch contains other identities' accounts too; only this identity's edges count.
                if (isset($principals[(string) $delegation->delegateAccountIdentifier()], $principals[(string) $delegation->delegatorAccountIdentifier()])) {
                    $delegatedAccounts[(string) $delegation->delegatorAccountIdentifier()] = true;
                    $identityDelegations[] = $delegation;
                }
            }
            $originals = array_diff_key($principals, $delegatedAccounts);
            if (count($originals) !== 1) {
                continue;
            }
            $original = array_values($originals)[0];
            if ($original->status() !== PrincipalStatus::ACTIVE || ! isset($activeAccounts[(string) $original->accountIdentifier()])) {
                continue;
            }
            $targetId = (string) $target->principalIdentifier();
            if ((string) $original->accountIdentifier() === (string) $target->accountIdentifier()) {
                $reachable[$targetId] = $target;

                continue;
            }
            foreach ($identityDelegations as $delegation) {
                if ((string) $delegation->delegateAccountIdentifier() === (string) $original->accountIdentifier()
                    && (string) $delegation->delegatorAccountIdentifier() === (string) $target->accountIdentifier()) {
                    // Preserve an explicitly empty final group set instead of reusing persisted memberships.
                    $originalGroups = $principalGroupsAccountIdentifier !== null
                        && (string) $principalGroupsAccountIdentifier === (string) $original->accountIdentifier()
                        ? $principalGroups : null;
                    $evaluations[$targetId] = new PolicyEvaluation($original, Action::DELEGATION_ACCOUNT_SWITCH, Resource::delegationAccount($original->accountIdentifier(), $delegation->delegationIdentifier(), $target->accountIdentifier()), $originalGroups);
                    $delegatedTargets[$targetId] = $target;

                    break;
                }
            }
        }
        if ($evaluations !== []) {
            foreach ($this->policyEvaluator->evaluateMany($evaluations) as $targetId => $allowed) {
                if ($allowed) {
                    $reachable[$targetId] = $delegatedTargets[$targetId];
                }
            }
        }

        return $reachable;
    }
}
