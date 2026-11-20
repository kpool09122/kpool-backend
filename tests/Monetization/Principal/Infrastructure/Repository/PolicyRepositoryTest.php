<?php

declare(strict_types=1);

namespace Tests\Monetization\Principal\Infrastructure\Repository;

use Application\Models\Monetization\Policy as PolicyEloquent;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Source\Monetization\Principal\Domain\Entity\Policy;
use Source\Monetization\Principal\Domain\ValueObject\Action;
use Source\Monetization\Principal\Domain\ValueObject\Condition;
use Source\Monetization\Principal\Domain\ValueObject\ConditionClause;
use Source\Monetization\Principal\Domain\ValueObject\ConditionKey;
use Source\Monetization\Principal\Domain\ValueObject\ConditionOperator;
use Source\Monetization\Principal\Domain\ValueObject\Effect;
use Source\Monetization\Principal\Domain\ValueObject\PolicyIdentifier;
use Source\Monetization\Principal\Domain\ValueObject\ResourceType;
use Source\Monetization\Principal\Domain\ValueObject\Statement;
use Source\Monetization\Principal\Infrastructure\Repository\PolicyRepository;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Tests\Helper\CreateAccount;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class PolicyRepositoryTest extends TestCase
{
    public function testAccountPolicyAndSystemPolicyCanShareNameWithoutLosingScope(): void
    {
        $account = new AccountIdentifier(StrTestHelper::generateUuid());
        CreateAccount::create((string) $account);
        $id = new PolicyIdentifier(StrTestHelper::generateUuid());
        $systemId = new PolicyIdentifier(StrTestHelper::generateUuid());
        $repository = new PolicyRepository();
        $repository->save(new Policy($id, 'read', [], $account));
        $repository->save(new Policy($systemId, 'read', [], null));
        self::assertSame([], $repository->findByIds([]));
        self::assertSame([], $repository->findByIds([new PolicyIdentifier(StrTestHelper::generateUuid())]));
        $policies = $repository->findByIds([$systemId, $id, $systemId, new PolicyIdentifier(StrTestHelper::generateUuid())]);
        self::assertEqualsCanonicalizing([(string) $id, (string) $systemId], array_keys($policies));
        self::assertSame((string) $id, (string) $policies[(string) $id]->policyIdentifier());
        self::assertSame((string) $systemId, (string) $policies[(string) $systemId]->policyIdentifier());
        self::assertSame((string) $account, (string) $repository->findByIds([$id])[(string) $id]->accountIdentifier());
        self::assertFalse($repository->findByIds([$id])[(string) $id]->isSystemPolicy());
        self::assertTrue($repository->findByIds([$systemId])[(string) $systemId]->isSystemPolicy());
    }

    public function testConditionRoundTripAndRemoval(): void
    {
        $id = new PolicyIdentifier(StrTestHelper::generateUuid());
        $clauses = [
            ['key' => 'resource:accountId', 'operator' => 'eq', 'value' => 'account-a'],
            ['key' => 'resource:accountId', 'operator' => 'ne', 'value' => 'account-b'],
            ['key' => 'resource:accountId', 'operator' => 'in', 'value' => ['account-a', 'account-c']],
            ['key' => 'resource:accountId', 'operator' => 'not_in', 'value' => []],
        ];
        $condition = new Condition(array_map(static fn (array $clause): ConditionClause => new ConditionClause(
            ConditionKey::from($clause['key']),
            ConditionOperator::from($clause['operator']),
            $clause['value'],
        ), $clauses));
        $statements = [
            new Statement(Effect::ALLOW, [Action::PRINCIPAL_GROUP_READ], [ResourceType::PRINCIPAL_GROUP], $condition),
            new Statement(Effect::DENY, [Action::PRINCIPAL_GROUP_MANAGE], [ResourceType::PRINCIPAL_GROUP], new Condition([])),
            new Statement(Effect::ALLOW, [Action::PRINCIPAL_GROUP_READ], [ResourceType::PRINCIPAL_GROUP]),
        ];
        $repository = new PolicyRepository();
        $repository->save(new Policy($id, 'conditions', $statements, null));

        $stored = PolicyEloquent::query()->findOrFail((string) $id)->statements;
        self::assertArrayHasKey('condition', $stored[0]);
        self::assertArrayHasKey('condition', $stored[1]);
        self::assertArrayHasKey('condition', $stored[2]);
        self::assertSame($clauses, $stored[0]['condition'] ?? null);
        self::assertSame([], $stored[1]['condition'] ?? null);
        self::assertNull($stored[2]['condition'] ?? null);
        self::assertEquals($statements, $repository->findByIds([$id])[(string) $id]->statements());

        $repository->save(new Policy($id, 'conditions', [$statements[2]], null));
        self::assertNull($repository->findByIds([$id])[(string) $id]->statements()[0]->condition());
    }

    /** @param array<string, mixed> $conditionData */
    #[DataProvider('legacyConditions')]
    public function testStatementsWithoutConditionsRemainCompatible(array $conditionData): void
    {
        $id = new PolicyIdentifier(StrTestHelper::generateUuid());
        PolicyEloquent::query()->create([
            'id' => (string) $id,
            'name' => 'legacy',
            'account_id' => null,
            'statements' => [array_merge([
                'effect' => Effect::ALLOW->value,
                'actions' => [Action::PRINCIPAL_GROUP_READ->value],
                'resource_types' => [ResourceType::PRINCIPAL_GROUP->value],
            ], $conditionData)],
        ]);

        $statement = (new PolicyRepository())->findByIds([$id])[(string) $id]->statements()[0];
        self::assertNull($statement->condition());
        self::assertSame(Effect::ALLOW, $statement->effect());
        self::assertSame([Action::PRINCIPAL_GROUP_READ], $statement->actions());
        self::assertSame([ResourceType::PRINCIPAL_GROUP], $statement->resourceTypes());
    }

    /** @return iterable<string, array{array<string, mixed>}> */
    public static function legacyConditions(): iterable
    {
        yield 'missing condition' => [[]];
        yield 'null condition' => [['condition' => null]];
    }
}
