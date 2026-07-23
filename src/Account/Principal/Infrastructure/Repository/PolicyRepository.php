<?php

declare(strict_types=1);

namespace Source\Account\Principal\Infrastructure\Repository;

use Application\Models\Account\Policy as PolicyEloquent;
use DateTimeImmutable;
use Source\Account\Principal\Domain\Entity\Policy;
use Source\Account\Principal\Domain\Repository\PolicyRepositoryInterface;
use Source\Account\Principal\Domain\ValueObject\Action;
use Source\Account\Principal\Domain\ValueObject\Effect;
use Source\Account\Principal\Domain\ValueObject\PolicyIdentifier;
use Source\Account\Principal\Domain\ValueObject\ResourceType;
use Source\Account\Principal\Domain\ValueObject\Statement;

class PolicyRepository implements PolicyRepositoryInterface
{
    public function save(Policy $policy): void
    {
        PolicyEloquent::query()->updateOrCreate(
            ['id' => (string) $policy->policyIdentifier()],
            [
                'name' => $policy->name(),
                'statements' => $this->serializeStatements($policy->statements()),
                'is_system_policy' => $policy->isSystemPolicy(),
            ]
        );
    }

    /**
     * @param PolicyIdentifier[] $policyIdentifiers
     * @return array<string, Policy>
     */
    public function findByIds(array $policyIdentifiers): array
    {
        if (empty($policyIdentifiers)) {
            return [];
        }

        $ids = array_map(static fn (PolicyIdentifier $policyIdentifier) => (string) $policyIdentifier, $policyIdentifiers);
        $policies = PolicyEloquent::query()
            ->whereIn('id', $ids)
            ->get();

        $result = [];
        foreach ($policies as $policy) {
            $result[$policy->id] = $this->toDomainEntity($policy);
        }

        return $result;
    }

    /**
     * @return Policy[]
     */
    public function findAll(): array
    {
        return PolicyEloquent::query()
            ->get()
            ->map(fn (PolicyEloquent $policy) => $this->toDomainEntity($policy))
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
            'actions' => array_map(static fn (Action $action) => $action->value, $statement->actions()),
            'resource_types' => array_map(static fn (ResourceType $resourceType) => $resourceType->value, $statement->resourceTypes()),
        ];
    }

    private function toDomainEntity(PolicyEloquent $eloquent): Policy
    {
        return new Policy(
            new PolicyIdentifier($eloquent->id),
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
            array_map(Action::from(...), $data['actions']),
            array_map(ResourceType::from(...), $data['resource_types']),
        );
    }
}
