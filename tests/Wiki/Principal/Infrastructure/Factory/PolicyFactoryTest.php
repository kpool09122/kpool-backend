<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Infrastructure\Factory;

use Illuminate\Contracts\Container\BindingResolutionException;
use Source\Shared\Application\Service\Uuid\UuidValidator;
use Source\Wiki\Principal\Domain\Factory\PolicyFactoryInterface;
use Source\Wiki\Principal\Domain\ValueObject\Effect;
use Source\Wiki\Principal\Domain\ValueObject\Statement;
use Source\Wiki\Principal\Infrastructure\Factory\PolicyFactory;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Tests\TestCase;

class PolicyFactoryTest extends TestCase
{
    /**
     * 正常系: DIが正しく動作すること.
     *
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $factory = $this->app->make(PolicyFactoryInterface::class);
        $this->assertInstanceOf(PolicyFactory::class, $factory);
    }

    /**
     * 正常系: 正しくPolicyエンティティが作成できること.
     *
     * @throws BindingResolutionException
     */
    public function testCreate(): void
    {
        $name = 'Test Policy';
        $statements = [
            new Statement(
                Effect::ALLOW,
                [Action::CREATE, Action::EDIT],
                ResourceType::cases(),
                null,
            ),
        ];
        $isSystemPolicy = true;

        $factory = $this->app->make(PolicyFactoryInterface::class);
        $policy = $factory->create(
            $name,
            $statements,
            $isSystemPolicy,
        );

        $this->assertTrue(UuidValidator::isValid((string) $policy->policyIdentifier()));
        $this->assertSame($name, $policy->name());
        $this->assertSame($statements, $policy->statements());
        $this->assertTrue($policy->isSystemPolicy());
        $this->assertNotNull($policy->createdAt());
    }

    /**
     * 正常系: isSystemPolicyがfalseの場合も正しく作成できること.
     *
     * @throws BindingResolutionException
     */
    public function testCreateWithNonSystemPolicy(): void
    {
        $name = 'Custom Policy';
        $statements = [
            new Statement(
                Effect::DENY,
                [Action::ROLLBACK],
                ResourceType::cases(),
                null,
            ),
        ];
        $isSystemPolicy = false;

        $factory = $this->app->make(PolicyFactoryInterface::class);
        $policy = $factory->create(
            $name,
            $statements,
            $isSystemPolicy,
        );

        $this->assertTrue(UuidValidator::isValid((string) $policy->policyIdentifier()));
        $this->assertSame($name, $policy->name());
        $this->assertSame($statements, $policy->statements());
        $this->assertFalse($policy->isSystemPolicy());
        $this->assertNotNull($policy->createdAt());
    }

    /**
     * 正常系: 空のStatementsでも正しく作成できること.
     *
     * @throws BindingResolutionException
     */
    public function testCreateWithEmptyStatements(): void
    {
        $name = 'Empty Policy';
        $statements = [];
        $isSystemPolicy = false;

        $factory = $this->app->make(PolicyFactoryInterface::class);
        $policy = $factory->create(
            $name,
            $statements,
            $isSystemPolicy,
        );

        $this->assertTrue(UuidValidator::isValid((string) $policy->policyIdentifier()));
        $this->assertSame($name, $policy->name());
        $this->assertSame([], $policy->statements());
        $this->assertFalse($policy->isSystemPolicy());
    }

    /**
     * 正常系: 複数のStatementsで正しく作成できること.
     *
     * @throws BindingResolutionException
     */
    public function testCreateWithMultipleStatements(): void
    {
        $name = 'Multi Statement Policy';
        $statements = [
            new Statement(
                Effect::ALLOW,
                [Action::CREATE, Action::EDIT, Action::SUBMIT],
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
        $isSystemPolicy = true;

        $factory = $this->app->make(PolicyFactoryInterface::class);
        $policy = $factory->create(
            $name,
            $statements,
            $isSystemPolicy,
        );

        $this->assertTrue(UuidValidator::isValid((string) $policy->policyIdentifier()));
        $this->assertCount(2, $policy->statements());
        $this->assertSame(Effect::ALLOW, $policy->statements()[0]->effect());
        $this->assertSame(Effect::DENY, $policy->statements()[1]->effect());
    }
}
