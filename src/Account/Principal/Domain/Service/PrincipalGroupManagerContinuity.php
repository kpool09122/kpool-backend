<?php

declare(strict_types=1);

namespace Source\Account\Principal\Domain\Service;

use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Principal\Domain\Entity\PrincipalGroup;
use Source\Account\Principal\Domain\Repository\PolicyRepositoryInterface;
use Source\Account\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\Account\Principal\Domain\ValueObject\Action;
use Source\Account\Principal\Domain\ValueObject\ConditionKey;
use Source\Account\Principal\Domain\ValueObject\ConditionOperator;
use Source\Account\Principal\Domain\ValueObject\Effect;
use Source\Account\Principal\Domain\ValueObject\PrincipalStatus;
use Source\Account\Principal\Domain\ValueObject\Resource;
use Source\Account\Principal\Domain\ValueObject\ResourceType;
use Source\Account\Principal\Domain\ValueObject\Statement;

readonly class PrincipalGroupManagerContinuity
{
    public function __construct(
        private RoleRepositoryInterface $roleRepository,
        private PolicyRepositoryInterface $policyRepository,
    ) {
    }

    /** @param array<PrincipalGroup> $principalGroups
     * @param array<string, Principal> $principalsById */
    public function hasManager(array $principalGroups, array $principalsById, Resource $resource): bool
    {
        $roleIdentifiers = [];
        foreach ($principalGroups as $group) {
            if ((string) $group->accountIdentifier() !== (string) $resource->accountIdentifier()) {
                continue;
            }
            foreach ($group->roles() as $identifier) {
                $roleIdentifiers[(string) $identifier] = $identifier;
            }
        }
        $roles = $this->roleRepository->findByIds(array_values($roleIdentifiers));
        $policyIdentifiers = [];
        foreach ($roles as $role) {
            foreach ($role->policies() as $identifier) {
                $policyIdentifiers[(string) $identifier] = $identifier;
            }
        }
        $policies = $this->policyRepository->findByIds(array_values($policyIdentifiers));

        foreach ($principalsById as $principal) {
            if ($principal->status() !== PrincipalStatus::ACTIVE
                || (string) $principal->accountIdentifier() !== (string) $resource->accountIdentifier()) {
                continue;
            }
            $roleIdentifiers = [];
            foreach ($principalGroups as $group) {
                if ((string) $group->accountIdentifier() !== (string) $principal->accountIdentifier() || ! $group->hasMember($principal->principalIdentifier())) {
                    continue;
                }
                foreach ($group->roles() as $identifier) {
                    $roleIdentifiers[(string) $identifier] = $identifier;
                }
            }
            $policyIdentifiers = [];
            foreach ($roleIdentifiers as $roleIdentifier) {
                $role = $roles[(string) $roleIdentifier] ?? null;
                if ($role === null) {
                    continue;
                }
                foreach ($role->policies() as $identifier) {
                    $policyIdentifiers[(string) $identifier] = $identifier;
                }
            }
            $allowed = false;
            $denied = false;
            foreach ($policyIdentifiers as $policyIdentifier) {
                $policy = $policies[(string) $policyIdentifier] ?? null;
                if ($policy === null) {
                    continue;
                }
                foreach ($policy->statements() as $statement) {
                    if (! $this->applies($statement, $resource)) {
                        continue;
                    }
                    $allowed = $allowed || $statement->effect() === Effect::ALLOW;
                    $denied = $denied || $statement->effect() === Effect::DENY;
                }
            }
            if ($allowed && ! $denied) {
                return true;
            }
        }

        return false;
    }

    private function applies(Statement $statement, Resource $resource): bool
    {
        if (! in_array(Action::PRINCIPAL_GROUP_MANAGE, $statement->actions(), true)
            || ! in_array(ResourceType::ACCOUNT, $statement->resourceTypes(), true)) {
            return false;
        }
        foreach ($statement->condition()?->clauses() ?? [] as $clause) {
            $value = match ($clause->key()) {
                ConditionKey::RESOURCE_ACCOUNT_TYPE => $resource->accountType()?->value,
                ConditionKey::RESOURCE_ACCOUNT_CATEGORY => $resource->accountCategory()?->value,
                default => null,
            };
            if ($value === null || ! match ($clause->operator()) {
                ConditionOperator::EQUALS => $value === $clause->value(),
                ConditionOperator::NOT_EQUALS => $value !== $clause->value(),
                ConditionOperator::IN => in_array($value, (array) $clause->value(), true),
                ConditionOperator::NOT_IN => ! in_array($value, (array) $clause->value(), true),
            }) {
                return false;
            }
        }

        return true;
    }
}
