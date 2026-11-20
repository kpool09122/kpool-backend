<?php

declare(strict_types=1);

namespace Source\Monetization\Principal\Infrastructure\Repository;

use Application\Models\Monetization\Policy as PolicyEloquent;
use Source\Monetization\Principal\Domain\Entity\Policy;
use Source\Monetization\Principal\Domain\Repository\PolicyRepositoryInterface;
use Source\Monetization\Principal\Domain\ValueObject\Action;
use Source\Monetization\Principal\Domain\ValueObject\Condition;
use Source\Monetization\Principal\Domain\ValueObject\ConditionClause;
use Source\Monetization\Principal\Domain\ValueObject\ConditionKey;
use Source\Monetization\Principal\Domain\ValueObject\ConditionOperator;
use Source\Monetization\Principal\Domain\ValueObject\Effect;
use Source\Monetization\Principal\Domain\ValueObject\PolicyIdentifier;
use Source\Monetization\Principal\Domain\ValueObject\ResourceType;
use Source\Monetization\Principal\Domain\ValueObject\Statement;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

class PolicyRepository implements PolicyRepositoryInterface
{
    public function save(Policy $policy): void
    {
        PolicyEloquent::query()->updateOrCreate(
            ['id' => (string) $policy->policyIdentifier()],
            [
                'account_id' => $policy->accountIdentifier() === null ? null : (string) $policy->accountIdentifier(),
                'name' => $policy->name(),
                'statements' => array_map($this->serializeStatement(...), $policy->statements()),
            ],
        );
    }

    /** @param PolicyIdentifier[] $identifiers
     * @return array<string, Policy> */
    public function findByIds(array $identifiers): array
    {
        if ($identifiers === []) {
            return [];
        }
        $models = PolicyEloquent::query()->whereIn('id', array_map(static fn (PolicyIdentifier $id): string => (string) $id, $identifiers))->get();
        $result = [];
        foreach ($models as $model) {
            $entity = new Policy(
                new PolicyIdentifier($model->id),
                $model->name,
                array_map($this->deserializeStatement(...), $model->statements),
                $model->account_id === null ? null : new AccountIdentifier($model->account_id),
            );
            $result[$model->id] = $entity;
        }

        return $result;
    }

    /**
     * @return array{effect: string, actions: list<string>, resource_types: list<string>, condition: list<array{key: string, operator: string, value: string|list<string>}>|null}
     */
    private function serializeStatement(Statement $statement): array
    {
        return [
            'effect' => $statement->effect()->value,
            'actions' => array_values(array_map(static fn (Action $action): string => $action->value, $statement->actions())),
            'resource_types' => array_values(array_map(static fn (ResourceType $resourceType): string => $resourceType->value, $statement->resourceTypes())),
            'condition' => $statement->condition() === null ? null : array_map(
                static fn (ConditionClause $clause): array => [
                    'key' => $clause->key()->value,
                    'operator' => $clause->operator()->value,
                    'value' => $clause->value(),
                ],
                $statement->condition()->clauses(),
            ),
        ];
    }

    /**
     * @param array{effect: string, actions: list<string>, resource_types: list<string>, condition?: list<array{key: string, operator: string, value: string|list<string>}>|null} $data
     */
    private function deserializeStatement(array $data): Statement
    {
        return new Statement(
            Effect::from($data['effect']),
            array_map(Action::from(...), $data['actions']),
            array_map(ResourceType::from(...), $data['resource_types']),
            isset($data['condition']) ? new Condition(array_map(
                static fn (array $clause): ConditionClause => new ConditionClause(
                    ConditionKey::from($clause['key']),
                    ConditionOperator::from($clause['operator']),
                    $clause['value'],
                ),
                $data['condition'],
            )) : null,
        );
    }
}
