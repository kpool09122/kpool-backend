<?php

declare(strict_types=1);

namespace Source\Account\Policy\Infrastructure\Repository;

use Application\Models\Account\AccountPolicy as AccountPolicyEloquent;
use Application\Models\Account\AccountRolePolicyAttachment as AccountRolePolicyAttachmentEloquent;
use DateTimeImmutable;
use Source\Account\IdentityGroup\Domain\ValueObject\AccountRole;
use Source\Account\Policy\Domain\Entity\AccountPolicy;
use Source\Account\Policy\Domain\Repository\AccountPolicyRepositoryInterface;
use Source\Account\Policy\Domain\ValueObject\AccountAction;
use Source\Account\Policy\Domain\ValueObject\AccountPolicyIdentifier;
use Source\Account\Policy\Domain\ValueObject\AccountResourceType;
use Source\Account\Policy\Domain\ValueObject\Effect;
use Source\Account\Policy\Domain\ValueObject\Statement;

class AccountPolicyRepository implements AccountPolicyRepositoryInterface
{
    public function save(AccountPolicy $policy): void
    {
        AccountPolicyEloquent::query()->updateOrCreate(
            ['id' => (string) $policy->accountPolicyIdentifier()],
            [
                'name' => $policy->name(),
                'statements' => $this->serializeStatements($policy->statements()),
                'is_system_policy' => $policy->isSystemPolicy(),
            ]
        );
    }

    public function attachToRole(AccountRole $role, AccountPolicyIdentifier $policyIdentifier): void
    {
        AccountRolePolicyAttachmentEloquent::query()->updateOrCreate(
            [
                'role' => $role->value,
                'policy_id' => (string) $policyIdentifier,
            ],
            []
        );
    }

    /**
     * @param AccountRole[] $roles
     * @return AccountPolicy[]
     */
    public function findByRoles(array $roles): array
    {
        if (empty($roles)) {
            return [];
        }

        $roleValues = array_map(static fn (AccountRole $role) => $role->value, $roles);
        $attachments = AccountRolePolicyAttachmentEloquent::query()
            ->with('policy')
            ->whereIn('role', $roleValues)
            ->get();

        $policies = [];
        /** @var AccountRolePolicyAttachmentEloquent $attachment */
        foreach ($attachments as $attachment) {
            if ($attachment->policy === null) {
                continue;
            }

            $policies[$attachment->policy->id] = $this->toDomainEntity($attachment->policy);
        }

        return array_values($policies);
    }

    /**
     * @return AccountPolicy[]
     */
    public function findAll(): array
    {
        return AccountPolicyEloquent::query()
            ->get()
            ->map(fn (AccountPolicyEloquent $policy) => $this->toDomainEntity($policy))
            ->all();
    }

    /**
     * @param Statement[] $statements
     * @return array<array{effect: string, actions: array<string>, resource_types: array<string>}>
     */
    private function serializeStatements(array $statements): array
    {
        return array_map($this->serializeStatement(...), $statements);
    }

    /**
     * @return array{effect: string, actions: array<string>, resource_types: array<string>}
     */
    private function serializeStatement(Statement $statement): array
    {
        return [
            'effect' => $statement->effect()->value,
            'actions' => array_map(static fn (AccountAction $action) => $action->value, $statement->actions()),
            'resource_types' => array_map(static fn (AccountResourceType $resourceType) => $resourceType->value, $statement->resourceTypes()),
        ];
    }

    private function toDomainEntity(AccountPolicyEloquent $eloquent): AccountPolicy
    {
        return new AccountPolicy(
            new AccountPolicyIdentifier($eloquent->id),
            $eloquent->name,
            $this->deserializeStatements($eloquent->statements),
            $eloquent->is_system_policy,
            new DateTimeImmutable($eloquent->created_at->toDateTimeString()),
        );
    }

    /**
     * @param array<array{effect: string, actions: array<string>, resource_types: array<string>}> $statementsData
     * @return Statement[]
     */
    private function deserializeStatements(array $statementsData): array
    {
        return array_map($this->deserializeStatement(...), $statementsData);
    }

    /**
     * @param array{effect: string, actions: array<string>, resource_types: array<string>} $data
     */
    private function deserializeStatement(array $data): Statement
    {
        return new Statement(
            Effect::from($data['effect']),
            array_map(AccountAction::from(...), $data['actions']),
            array_map(AccountResourceType::from(...), $data['resource_types']),
        );
    }
}
