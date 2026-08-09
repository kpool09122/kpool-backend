<?php

declare(strict_types=1);

namespace Application\Http\Context;

use Application\Models\Account\Account as AccountModel;
use Application\Models\Account\Principal as PrincipalModel;
use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Domain\ValueObject\AccountType;
use Source\Account\Principal\Domain\Entity\Policy;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Principal\Domain\Repository\PolicyRepositoryInterface;
use Source\Account\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Account\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\Account\Principal\Domain\ValueObject\Action;
use Source\Account\Principal\Domain\ValueObject\Condition;
use Source\Account\Principal\Domain\ValueObject\ConditionClause;
use Source\Account\Principal\Domain\ValueObject\ResourceType;
use Source\Account\Principal\Domain\ValueObject\RoleIdentifier;
use Source\Account\Principal\Domain\ValueObject\Statement;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class AccountResolver
{
    public function __construct(
        private PrincipalGroupRepositoryInterface $principalGroupRepository,
        private RoleRepositoryInterface $roleRepository,
        private PolicyRepositoryInterface $policyRepository,
    ) {
    }

    /** @throws AccountNotFoundException */
    public function resolve(IdentityIdentifier $identityIdentifier): AccountContext
    {
        $principal = PrincipalModel::query()
            ->where('identity_id', (string) $identityIdentifier)
            ->first();

        if ($principal === null) {
            throw new AccountNotFoundException('Account context not found.');
        }

        $account = AccountModel::query()
            ->where('id', $principal->account_id)
            ->first();

        if ($account === null) {
            throw new AccountNotFoundException('Account context not found.');
        }

        $accountPrincipal = new Principal(
            new PrincipalIdentifier($principal->id),
            new IdentityIdentifier($principal->identity_id),
            new AccountIdentifier($principal->account_id),
        );

        $principalGroups = $this->principalGroupRepository->findByAccountIdAndPrincipal(
            $accountPrincipal->accountIdentifier(),
            $accountPrincipal->principalIdentifier(),
        );
        $roleIdentifiers = [];
        foreach ($principalGroups as $principalGroup) {
            foreach ($principalGroup->roles() as $roleIdentifier) {
                $roleIdentifiers[(string) $roleIdentifier] = $roleIdentifier;
            }
        }

        return new AccountContext(
            principal: $accountPrincipal,
            accountType: AccountType::from($account->type),
            accountPolicies: $this->effectivePolicies(array_values($roleIdentifiers)),
        );
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
     * @return array{effect: string, actions: array<int, string>, resourceTypes: array<int, string>, condition: array{clauses: array<int, array{field: string, operator: string, value: string|bool}>}|null}
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
     * @return array{clauses: array<int, array{field: string, operator: string, value: string|bool}>}|null
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
     * @return array{field: string, operator: string, value: string|bool}
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
