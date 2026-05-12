<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Infrastructure\Query;

use Application\Models\Wiki\Policy as PolicyModel;
use Application\Models\Wiki\Principal as PrincipalModel;
use Source\Wiki\Principal\Application\UseCase\Query\GetCurrentPrincipal\GetCurrentPrincipalInputPort;
use Source\Wiki\Principal\Application\UseCase\Query\GetCurrentPrincipal\GetCurrentPrincipalInterface;
use Source\Wiki\Principal\Application\UseCase\Query\PrincipalReadModel;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;

readonly class GetCurrentPrincipal implements GetCurrentPrincipalInterface
{
    /**
     * @throws PrincipalNotFoundException
     */
    public function process(GetCurrentPrincipalInputPort $input): PrincipalReadModel
    {
        $principal = PrincipalModel::query()
            ->with('memberships.principalGroup.roleAttachments.role.policyAttachments.policy')
            ->where('identity_id', (string) $input->identityIdentifier())
            ->first();

        if ($principal === null) {
            throw new PrincipalNotFoundException();
        }

        return $this->toReadModel($principal);
    }

    private function toReadModel(PrincipalModel $principal): PrincipalReadModel
    {
        return new PrincipalReadModel(
            principalIdentifier: $principal->id,
            identityIdentifier: $principal->identity_id,
            isDelegatedPrincipal: $principal->delegation_identifier !== null,
            isEnabled: $principal->enabled,
            policies: $this->effectivePolicies($principal),
        );
    }

    /**
     * @return array<int, array{policyIdentifier: string, name: string, isSystemPolicy: bool, statements: array<int, array<string, mixed>>}>
     */
    private function effectivePolicies(PrincipalModel $principal): array
    {
        $policies = [];

        foreach ($principal->memberships as $membership) {
            $principalGroup = $membership->principalGroup;
            if ($principalGroup === null) {
                continue;
            }

            foreach ($principalGroup->roleAttachments as $roleAttachment) {
                $role = $roleAttachment->role;
                if ($role === null) {
                    continue;
                }

                foreach ($role->policyAttachments as $policyAttachment) {
                    $policy = $policyAttachment->policy;
                    if ($policy === null) {
                        continue;
                    }

                    $policies[$policy->id] = $policy;
                }
            }
        }

        ksort($policies);

        return array_map(
            fn (PolicyModel $policy) => $this->toPolicyArray($policy),
            array_values($policies)
        );
    }

    /**
     * @return array{policyIdentifier: string, name: string, isSystemPolicy: bool, statements: array<int, array<string, mixed>>}
     */
    private function toPolicyArray(PolicyModel $policy): array
    {
        return [
            'policyIdentifier' => $policy->id,
            'name' => $policy->name,
            'isSystemPolicy' => $policy->is_system_policy,
            'statements' => array_map($this->toStatementArray(...), $policy->statements),
        ];
    }

    /**
     * @param array{effect: string, actions: array<string>, resource_types: array<string>, condition?: array<array{key: string, operator: string, value: string|bool}>|null} $statement
     * @return array{effect: string, actions: array<string>, resourceTypes: array<string>, condition: array{clauses: array<int, array{field: string, operator: string, value: string|bool}>}|null}
     */
    private function toStatementArray(array $statement): array
    {
        return [
            'effect' => $statement['effect'],
            'actions' => $statement['actions'],
            'resourceTypes' => $statement['resource_types'],
            'condition' => $this->toConditionArray($statement['condition'] ?? null),
        ];
    }

    /**
     * @param array<array{key: string, operator: string, value: string|bool}>|null $condition
     * @return array{clauses: array<int, array{field: string, operator: string, value: string|bool}>}|null
     */
    private function toConditionArray(?array $condition): ?array
    {
        if ($condition === null) {
            return null;
        }

        return [
            'clauses' => array_map(
                static fn (array $clause) => [
                    'field' => $clause['key'],
                    'operator' => $clause['operator'],
                    'value' => $clause['value'],
                ],
                $condition
            ),
        ];
    }
}
