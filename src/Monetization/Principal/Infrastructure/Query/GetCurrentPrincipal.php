<?php

declare(strict_types=1);

namespace Source\Monetization\Principal\Infrastructure\Query;

use Application\Models\Monetization\Policy as PolicyModel;
use Application\Models\Monetization\Principal as PrincipalModel;
use Application\Models\Monetization\RolePolicyAttachment;
use Source\Monetization\Principal\Application\UseCase\Query\GetCurrentPrincipal\GetCurrentPrincipalInputPort;
use Source\Monetization\Principal\Application\UseCase\Query\GetCurrentPrincipal\GetCurrentPrincipalInterface;
use Source\Monetization\Principal\Application\UseCase\Query\PrincipalReadModel;
use Source\Monetization\Shared\Domain\Exception\PrincipalNotFoundException;

readonly class GetCurrentPrincipal implements GetCurrentPrincipalInterface
{
    /** @throws PrincipalNotFoundException */
    public function process(GetCurrentPrincipalInputPort $input): PrincipalReadModel
    {
        $principal = PrincipalModel::query()
            ->where('identity_id', (string) $input->identityIdentifier())
            ->where('account_id', (string) $input->accountIdentifier())
            ->first();

        if ($principal === null) {
            throw new PrincipalNotFoundException();
        }

        return new PrincipalReadModel(
            principalIdentifier: $principal->id,
            identityIdentifier: $principal->identity_id,
            status: $principal->status,
            policies: $this->policies($principal),
            accountPrincipalCount: PrincipalModel::query()
                ->where('account_id', $principal->account_id)
                ->count(),
        );
    }

    /** @return array<int, array{policyIdentifier: string, name: string, statements: array<int, array{effect: string, actions: array<int, string>, resourceTypes: array<int, string>, condition: array{clauses: list<array{field: string, operator: string, value: string|list<string>}>}|null}>}> */
    private function policies(PrincipalModel $principal): array
    {
        $policyIdentifiers = RolePolicyAttachment::query()
            ->select('policy_id')
            ->join('monetization_principal_group_role_attachments as roles', 'roles.role_id', '=', 'monetization_role_policy_attachments.role_id')
            ->join('monetization_principal_group_memberships as memberships', 'memberships.principal_group_id', '=', 'roles.principal_group_id')
            ->join('monetization_principal_groups as groups', 'groups.id', '=', 'memberships.principal_group_id')
            ->join('monetization_roles as role_models', 'role_models.id', '=', 'roles.role_id')
            ->where('memberships.principal_id', $principal->id)
            ->where('groups.account_id', $principal->account_id)
            ->where(static fn ($query) => $query->whereNull('role_models.account_id')->orWhere('role_models.account_id', $principal->account_id));

        return PolicyModel::query()
            ->whereIn('id', $policyIdentifiers)
            ->where(static fn ($query) => $query->whereNull('account_id')->orWhere('account_id', $principal->account_id))
            ->orderBy('id')
            ->get()
            ->map(static fn (PolicyModel $policy): array => [
                'policyIdentifier' => $policy->id,
                'name' => $policy->name,
                'statements' => array_map(static fn (array $statement): array => [
                    'effect' => $statement['effect'],
                    'actions' => $statement['actions'],
                    'resourceTypes' => $statement['resource_types'],
                    'condition' => isset($statement['condition']) ? ['clauses' => array_map(static fn (array $clause): array => ['field' => $clause['key'], 'operator' => $clause['operator'], 'value' => $clause['value']], $statement['condition'])] : null,
                ], array_values($policy->statements)),
            ])->all();
    }
}
