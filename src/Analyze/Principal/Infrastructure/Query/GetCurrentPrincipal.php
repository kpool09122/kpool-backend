<?php

declare(strict_types=1);

namespace Source\Analyze\Principal\Infrastructure\Query;

use Application\Models\Analyze\Policy as PolicyModel;
use Application\Models\Analyze\Principal as PrincipalModel;
use Application\Models\Analyze\RolePolicyAttachment;
use Source\Analyze\Principal\Application\UseCase\Query\GetCurrentPrincipal\GetCurrentPrincipalInputPort;
use Source\Analyze\Principal\Application\UseCase\Query\GetCurrentPrincipal\GetCurrentPrincipalInterface;
use Source\Analyze\Principal\Application\UseCase\Query\PrincipalReadModel;
use Source\Analyze\Shared\Domain\Exception\PrincipalNotFoundException;

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
        );
    }

    /** @return array<int, array{policyIdentifier: string, name: string, statements: array<int, array{effect: string, actions: array<int, string>, resourceTypes: array<int, string>}>}> */
    private function policies(PrincipalModel $principal): array
    {
        $policyIdentifiers = RolePolicyAttachment::query()
            ->select('policy_id')
            ->join('analyze_principal_group_role_attachments as roles', 'roles.role_id', '=', 'analyze_role_policy_attachments.role_id')
            ->join('analyze_principal_group_memberships as memberships', 'memberships.principal_group_id', '=', 'roles.principal_group_id')
            ->join('analyze_principal_groups as groups', 'groups.id', '=', 'memberships.principal_group_id')
            ->join('analyze_roles as role_models', 'role_models.id', '=', 'roles.role_id')
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
                'statements' => $policy->statementValues(),
            ])->all();
    }
}
