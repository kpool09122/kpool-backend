<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Application\UseCase\Command\CreatePolicy;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Wiki\Principal\Application\UseCase\Command\CreatePolicy\CreatePolicy;
use Source\Wiki\Principal\Application\UseCase\Command\CreatePolicy\CreatePolicyInput;
use Source\Wiki\Principal\Application\UseCase\Command\CreatePolicy\CreatePolicyInterface;
use Source\Wiki\Principal\Domain\Entity\Policy;
use Source\Wiki\Principal\Domain\Factory\PolicyFactoryInterface;
use Source\Wiki\Principal\Domain\Repository\PolicyRepositoryInterface;
use Source\Wiki\Principal\Domain\ValueObject\Effect;
use Source\Wiki\Principal\Domain\ValueObject\PolicyIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\Statement;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class CreatePolicyTest extends TestCase
{
    /**
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $repository = Mockery::mock(PolicyRepositoryInterface::class);
        $factory = Mockery::mock(PolicyFactoryInterface::class);
        $this->app->instance(PolicyRepositoryInterface::class, $repository);
        $this->app->instance(PolicyFactoryInterface::class, $factory);
        $useCase = $this->app->make(CreatePolicyInterface::class);
        $this->assertInstanceOf(CreatePolicy::class, $useCase);
    }

    /**
     * @throws BindingResolutionException
     */
    public function testProcess(): void
    {
        $testData = $this->createDummyTestData();

        $repository = Mockery::mock(PolicyRepositoryInterface::class);
        $repository->shouldReceive('save')
            ->once()
            ->with($testData->policy)
            ->andReturnNull();

        $factory = Mockery::mock(PolicyFactoryInterface::class);
        $factory->shouldReceive('create')
            ->once()
            ->with(
                $testData->name,
                $testData->statements,
                $testData->isSystemPolicy,
            )
            ->andReturn($testData->policy);

        $this->app->instance(PolicyRepositoryInterface::class, $repository);
        $this->app->instance(PolicyFactoryInterface::class, $factory);

        $useCase = $this->app->make(CreatePolicyInterface::class);

        $policy = $useCase->process($testData->input);

        $this->assertSame((string) $testData->policyIdentifier, (string) $policy->policyIdentifier());
        $this->assertSame($testData->name, $policy->name());
        $this->assertSame($testData->statements, $policy->statements());
        $this->assertSame($testData->isSystemPolicy, $policy->isSystemPolicy());
    }

    private function createDummyTestData(): CreatePolicyTestData
    {
        $policyIdentifier = new PolicyIdentifier(StrTestHelper::generateUuid());
        $name = 'Test Policy';
        $statements = [
            new Statement(
                Effect::ALLOW,
                [Action::CREATE, Action::EDIT],
                ResourceType::cases(),
                null,
            ),
        ];
        $isSystemPolicy = false;

        $policy = new Policy(
            $policyIdentifier,
            $name,
            $statements,
            $isSystemPolicy,
            new DateTimeImmutable(),
        );

        $input = new CreatePolicyInput(
            $name,
            $statements,
            $isSystemPolicy,
        );

        return new CreatePolicyTestData(
            $policyIdentifier,
            $name,
            $statements,
            $isSystemPolicy,
            $policy,
            $input,
        );
    }
}

readonly class CreatePolicyTestData
{
    /**
     * @param Statement[] $statements
     */
    public function __construct(
        public PolicyIdentifier $policyIdentifier,
        public string $name,
        public array $statements,
        public bool $isSystemPolicy,
        public Policy $policy,
        public CreatePolicyInput $input,
    ) {
    }
}
