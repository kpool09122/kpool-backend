<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Domain\Entity;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Principal\Domain\Entity\Policy;
use Source\Wiki\Principal\Domain\ValueObject\Effect;
use Source\Wiki\Principal\Domain\ValueObject\PolicyIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\Statement;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Tests\Helper\StrTestHelper;

class PolicyTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     */
    public function test__construct(): void
    {
        $policyIdentifier = new PolicyIdentifier(StrTestHelper::generateUuid());
        $name = 'Full Access';
        $statements = [
            new Statement(
                Effect::ALLOW,
                Action::cases(),
                ResourceType::cases(),
                null,
            ),
        ];
        $isSystemPolicy = true;
        $createdAt = new DateTimeImmutable();

        $policy = new Policy(
            $policyIdentifier,
            $name,
            $statements,
            $isSystemPolicy,
            $createdAt,
        );

        $this->assertSame($policyIdentifier, $policy->policyIdentifier());
        $this->assertSame($name, $policy->name());
        $this->assertSame($statements, $policy->statements());
        $this->assertTrue($policy->isSystemPolicy());
        $this->assertSame($createdAt, $policy->createdAt());
    }

    /**
     * 正常系: 非システムポリシーが作成できること
     */
    public function testNonSystemPolicy(): void
    {
        $policy = $this->createPolicy(isSystemPolicy: false);

        $this->assertFalse($policy->isSystemPolicy());
    }

    /**
     * 正常系: 複数のStatementsを持つPolicyが作成できること
     */
    public function testPolicyWithMultipleStatements(): void
    {
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

        $policy = $this->createPolicy(statements: $statements);

        $this->assertCount(2, $policy->statements());
        $this->assertSame(Effect::ALLOW, $policy->statements()[0]->effect());
        $this->assertSame(Effect::DENY, $policy->statements()[1]->effect());
    }

    /**
     * 正常系: 空のStatementsでPolicyが作成できること
     */
    public function testPolicyWithEmptyStatements(): void
    {
        $policy = $this->createPolicy(statements: []);

        $this->assertSame([], $policy->statements());
    }

    /**
     * @param Statement[] $statements
     */
    private function createPolicy(
        ?array $statements = null,
        bool $isSystemPolicy = false,
    ): Policy {
        return new Policy(
            new PolicyIdentifier(StrTestHelper::generateUuid()),
            'Test Policy',
            $statements ?? [
                new Statement(
                    Effect::ALLOW,
                    [Action::CREATE],
                    [ResourceType::TALENT],
                    null,
                ),
            ],
            $isSystemPolicy,
            new DateTimeImmutable(),
        );
    }
}
