<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Infrastructure\Repository;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use PHPUnit\Framework\Attributes\Group;
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
use Source\Wiki\Principal\Infrastructure\Repository\PolicyRepository;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Tests\Helper\CreatePolicy;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class PolicyRepositoryTest extends TestCase
{
    /**
     * 正常系: DIが正しく動作すること.
     *
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $repository = $this->app->make(PolicyRepositoryInterface::class);
        $this->assertInstanceOf(PolicyRepository::class, $repository);
    }

    /**
     * 正常系: 正しくPolicyを保存できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSave(): void
    {
        $policyId = StrTestHelper::generateUuid();

        $policy = new Policy(
            new PolicyIdentifier($policyId),
            'Full Access',
            [
                new Statement(
                    Effect::ALLOW,
                    Action::cases(),
                    ResourceType::cases(),
                    null,
                ),
            ],
            true,
            new DateTimeImmutable(),
        );

        $repository = $this->app->make(PolicyRepositoryInterface::class);
        $repository->save($policy);

        $this->assertDatabaseHas('policies', [
            'id' => $policyId,
            'name' => 'Full Access',
            'is_system_policy' => true,
        ]);
    }

    /**
     * 正常系: 正しくIDに紐づくPolicyを取得できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindById(): void
    {
        $policyId = StrTestHelper::generateUuid();

        CreatePolicy::create(
            new PolicyIdentifier($policyId),
            [
                'name' => 'Test Policy',
                'is_system_policy' => true,
            ]
        );

        $repository = $this->app->make(PolicyRepositoryInterface::class);
        $result = $repository->findById(new PolicyIdentifier($policyId));

        $this->assertNotNull($result);
        $this->assertSame($policyId, (string) $result->policyIdentifier());
        $this->assertSame('Test Policy', $result->name());
        $this->assertTrue($result->isSystemPolicy());
        $this->assertNotNull($result->createdAt());
    }

    /**
     * 正常系: 指定したIDを持つPolicyが存在しない場合、NULLが返却されること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdWhenNotFound(): void
    {
        $repository = $this->app->make(PolicyRepositoryInterface::class);
        $result = $repository->findById(new PolicyIdentifier(StrTestHelper::generateUuid()));

        $this->assertNull($result);
    }

    /**
     * 正常系: 全てのPolicyを取得できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindAll(): void
    {
        $policyId1 = StrTestHelper::generateUuid();
        $policyId2 = StrTestHelper::generateUuid();

        CreatePolicy::create(
            new PolicyIdentifier($policyId1),
            ['name' => 'Policy 1']
        );
        CreatePolicy::create(
            new PolicyIdentifier($policyId2),
            ['name' => 'Policy 2']
        );

        $repository = $this->app->make(PolicyRepositoryInterface::class);
        $result = $repository->findAll();

        $this->assertCount(2, $result);
        $names = array_map(static fn ($p) => $p->name(), $result);
        $this->assertContains('Policy 1', $names);
        $this->assertContains('Policy 2', $names);
    }

    /**
     * 正常系: Policyが存在しない場合、空配列が返却されること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindAllWhenEmpty(): void
    {
        $repository = $this->app->make(PolicyRepositoryInterface::class);
        $result = $repository->findAll();

        $this->assertSame([], $result);
    }

    /**
     * 正常系: 正しくPolicyを削除できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testDelete(): void
    {
        $policyId = StrTestHelper::generateUuid();

        CreatePolicy::create(new PolicyIdentifier($policyId));

        // 削除前に存在確認
        $this->assertDatabaseHas('policies', ['id' => $policyId]);

        $repository = $this->app->make(PolicyRepositoryInterface::class);

        $policy = new Policy(
            new PolicyIdentifier($policyId),
            'Test Policy',
            [],
            false,
            new DateTimeImmutable(),
        );

        $repository->delete($policy);

        // 削除後の確認
        $this->assertDatabaseMissing('policies', ['id' => $policyId]);
    }

    /**
     * 正常系: 既存のPolicyを更新できること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveUpdatesExistingPolicy(): void
    {
        $policyId = StrTestHelper::generateUuid();

        CreatePolicy::create(
            new PolicyIdentifier($policyId),
            [
                'name' => 'Original Name',
                'is_system_policy' => false,
            ]
        );

        // 更新前の確認
        $this->assertDatabaseHas('policies', [
            'id' => $policyId,
            'name' => 'Original Name',
            'is_system_policy' => false,
        ]);

        $repository = $this->app->make(PolicyRepositoryInterface::class);

        $updatedPolicy = new Policy(
            new PolicyIdentifier($policyId),
            'Updated Name',
            [],
            true,
            new DateTimeImmutable(),
        );

        $repository->save($updatedPolicy);

        // 更新後の確認
        $this->assertDatabaseHas('policies', [
            'id' => $policyId,
            'name' => 'Updated Name',
            'is_system_policy' => true,
        ]);
    }

    /**
     * 正常系: Condition付きのStatementが正しくシリアライズ・デシリアライズされること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveAndRetrieveWithCondition(): void
    {
        $policyId = StrTestHelper::generateUuid();

        $condition = new Condition([
            new ConditionClause(
                ConditionKey::RESOURCE_AGENCY_ID,
                ConditionOperator::EQUALS,
                ConditionValue::PRINCIPAL_AGENCY_ID,
            ),
        ]);

        $statements = [
            new Statement(
                Effect::ALLOW,
                [Action::APPROVE, Action::REJECT],
                [ResourceType::TALENT],
                $condition,
            ),
        ];

        $policy = new Policy(
            new PolicyIdentifier($policyId),
            'Agency Management',
            $statements,
            true,
            new DateTimeImmutable(),
        );

        $repository = $this->app->make(PolicyRepositoryInterface::class);
        $repository->save($policy);

        $result = $repository->findById(new PolicyIdentifier($policyId));

        $this->assertNotNull($result);
        $this->assertCount(1, $result->statements());

        $retrievedStatement = $result->statements()[0];
        $this->assertSame(Effect::ALLOW, $retrievedStatement->effect());
        $this->assertSame([Action::APPROVE, Action::REJECT], $retrievedStatement->actions());
        $this->assertSame([ResourceType::TALENT], $retrievedStatement->resourceTypes());

        $retrievedCondition = $retrievedStatement->condition();
        $this->assertNotNull($retrievedCondition);
        $this->assertCount(1, $retrievedCondition->clauses());

        $clause = $retrievedCondition->clauses()[0];
        $this->assertSame(ConditionKey::RESOURCE_AGENCY_ID, $clause->key());
        $this->assertSame(ConditionOperator::EQUALS, $clause->operator());
        $this->assertSame(ConditionValue::PRINCIPAL_AGENCY_ID, $clause->value());
    }

    /**
     * 正常系: 複数のStatementが正しくシリアライズ・デシリアライズされること
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveAndRetrieveWithMultipleStatements(): void
    {
        $policyId = StrTestHelper::generateUuid();

        $statements = [
            new Statement(
                Effect::ALLOW,
                [Action::CREATE, Action::EDIT],
                ResourceType::cases(),
                null,
            ),
            new Statement(
                Effect::DENY,
                [Action::ROLLBACK],
                ResourceType::cases(),
                null,
            ),
        ];

        $policy = new Policy(
            new PolicyIdentifier($policyId),
            'Mixed Policy',
            $statements,
            false,
            new DateTimeImmutable(),
        );

        $repository = $this->app->make(PolicyRepositoryInterface::class);
        $repository->save($policy);

        $result = $repository->findById(new PolicyIdentifier($policyId));

        $this->assertNotNull($result);
        $this->assertCount(2, $result->statements());
        $this->assertSame(Effect::ALLOW, $result->statements()[0]->effect());
        $this->assertSame(Effect::DENY, $result->statements()[1]->effect());
    }
}
