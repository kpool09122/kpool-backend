<?php

declare(strict_types=1);

namespace Source\Monetization\Principal\Infrastructure\Repository;

use Application\Models\Monetization\Policy as PolicyEloquent;
use Source\Monetization\Principal\Domain\Entity\Policy;
use Source\Monetization\Principal\Domain\Repository\PolicyRepositoryInterface;
use Source\Monetization\Principal\Domain\ValueObject\Action;
use Source\Monetization\Principal\Domain\ValueObject\Effect;
use Source\Monetization\Principal\Domain\ValueObject\PolicyIdentifier;
use Source\Monetization\Principal\Domain\ValueObject\ResourceType;
use Source\Monetization\Principal\Domain\ValueObject\Statement;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

class PolicyRepository implements PolicyRepositoryInterface
{
    public function save(Policy $policy): void
    {
        PolicyEloquent::query()->updateOrCreate(['id' => (string) $policy->policyIdentifier()], ['account_id' => $policy->accountIdentifier() === null ? null : (string) $policy->accountIdentifier(), 'name' => $policy->name(), 'statements' => array_map(static fn (Statement $s): array => ['effect' => $s->effect()->value, 'actions' => array_map(static fn (Action $a): string => $a->value, $s->actions()), 'resource_types' => array_map(static fn (ResourceType $r): string => $r->value, $s->resourceTypes())], $policy->statements())]);
    }

    /** @param PolicyIdentifier[] $identifiers
     * @return Policy[] */
    public function findByIds(array $identifiers): array
    {
        if ($identifiers === []) {
            return [];
        }
        $models = PolicyEloquent::query()->whereIn('id', array_map(static fn (PolicyIdentifier $id): string => (string) $id, $identifiers))->get();
        $result = [];
        foreach ($models as $model) {
            $entity = new Policy(new PolicyIdentifier($model->id), $model->name, array_map(static fn (array $s): Statement => new Statement(Effect::from($s['effect']), array_map(Action::from(...), $s['actions']), array_map(ResourceType::from(...), $s['resource_types'])), $model->statements), $model->account_id === null ? null : new AccountIdentifier($model->account_id));
            $result[] = $entity;
        }

        return $result;
    }
}
