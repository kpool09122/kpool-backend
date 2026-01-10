<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Infrastructure\Repository;

use Application\Models\Wiki\Policy as PolicyEloquent;
use DateTimeImmutable;
use Source\Wiki\Principal\Domain\Entity\Policy;
use Source\Wiki\Principal\Domain\Repository\PolicyRepositoryInterface;
use Source\Wiki\Principal\Domain\ValueObject\Condition;
use Source\Wiki\Principal\Domain\ValueObject\ConditionClause;
use Source\Wiki\Principal\Domain\ValueObject\ConditionKey;
use Source\Wiki\Principal\Domain\ValueObject\ConditionOperator;
use Source\Wiki\Principal\Domain\ValueObject\ConditionValue;
use Source\Wiki\Principal\Domain\ValueObject\Effect;
use Source\Wiki\Principal\Domain\ValueObject\PolicyIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\Statement;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

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

    public function findById(PolicyIdentifier $policyIdentifier): ?Policy
    {
        $eloquent = PolicyEloquent::query()
            ->where('id', (string) $policyIdentifier)
            ->first();

        if ($eloquent === null) {
            return null;
        }

        return $this->toDomainEntity($eloquent);
    }

    /**
     * @return array<Policy>
     */
    public function findAll(): array
    {
        $eloquentModels = PolicyEloquent::query()->get();

        return $eloquentModels->map(fn (PolicyEloquent $eloquent) => $this->toDomainEntity($eloquent))->all();
    }

    public function delete(Policy $policy): void
    {
        PolicyEloquent::query()
            ->where('id', (string) $policy->policyIdentifier())
            ->delete();
    }

    /**
     * @param Statement[] $statements
     * @return array<array{effect: string, actions: array<string>, resource_types: array<string>, condition: array<array{key: string, operator: string, value: string|bool}>|null}>
     */
    private function serializeStatements(array $statements): array
    {
        return array_map(
            fn (Statement $statement) => $this->serializeStatement($statement),
            $statements
        );
    }

    /**
     * @return array{effect: string, actions: array<string>, resource_types: array<string>, condition: array<array{key: string, operator: string, value: string|bool}>|null}
     */
    private function serializeStatement(Statement $statement): array
    {
        return [
            'effect' => $statement->effect()->value,
            'actions' => array_map(fn (Action $action) => $action->value, $statement->actions()),
            'resource_types' => array_map(fn (ResourceType $resourceType) => $resourceType->value, $statement->resourceTypes()),
            'condition' => $statement->condition() !== null
                ? $this->serializeCondition($statement->condition())
                : null,
        ];
    }

    /**
     * @return array<array{key: string, operator: string, value: string|bool}>
     */
    private function serializeCondition(Condition $condition): array
    {
        return array_map(
            fn (ConditionClause $clause) => [
                'key' => $clause->key()->value,
                'operator' => $clause->operator()->value,
                'value' => $clause->value() instanceof ConditionValue
                    ? $clause->value()->value
                    : $clause->value(),
            ],
            $condition->clauses()
        );
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
     * @param array<array{effect: string, actions: array<string>, resource_types: array<string>, condition: array<array{key: string, operator: string, value: string|bool}>|null}> $statementsData
     * @return Statement[]
     */
    private function deserializeStatements(array $statementsData): array
    {
        return array_map(
            fn (array $statementData) => $this->deserializeStatement($statementData),
            $statementsData
        );
    }

    /**
     * @param array{effect: string, actions: array<string>, resource_types: array<string>, condition: array<array{key: string, operator: string, value: string|bool}>|null} $data
     */
    private function deserializeStatement(array $data): Statement
    {
        return new Statement(
            Effect::from($data['effect']),
            array_map(fn (string $action) => Action::from($action), $data['actions']),
            array_map(fn (string $resourceType) => ResourceType::from($resourceType), $data['resource_types']),
            $data['condition'] !== null
                ? $this->deserializeCondition($data['condition'])
                : null,
        );
    }

    /**
     * @param array<array{key: string, operator: string, value: string|bool}> $conditionData
     */
    private function deserializeCondition(array $conditionData): Condition
    {
        $clauses = array_map(
            fn (array $clauseData) => new ConditionClause(
                ConditionKey::from($clauseData['key']),
                ConditionOperator::from($clauseData['operator']),
                $this->deserializeConditionValue($clauseData['value']),
            ),
            $conditionData
        );

        return new Condition($clauses);
    }

    private function deserializeConditionValue(string|bool $value): ConditionValue|string|bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $conditionValue = ConditionValue::tryFrom($value);

        return $conditionValue ?? $value;
    }
}
