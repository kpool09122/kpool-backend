<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Application\UseCase\Command\DeletePolicy;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Wiki\Principal\Application\Exception\CannotDeleteSystemPolicyException;
use Source\Wiki\Principal\Application\Exception\PolicyNotFoundException;
use Source\Wiki\Principal\Application\UseCase\Command\DeletePolicy\DeletePolicy;
use Source\Wiki\Principal\Application\UseCase\Command\DeletePolicy\DeletePolicyInput;
use Source\Wiki\Principal\Application\UseCase\Command\DeletePolicy\DeletePolicyInterface;
use Source\Wiki\Principal\Domain\Entity\Policy;
use Source\Wiki\Principal\Domain\Repository\PolicyRepositoryInterface;
use Source\Wiki\Principal\Domain\ValueObject\Effect;
use Source\Wiki\Principal\Domain\ValueObject\PolicyIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\Statement;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class DeletePolicyTest extends TestCase
{
    /**
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $repository = Mockery::mock(PolicyRepositoryInterface::class);
        $this->app->instance(PolicyRepositoryInterface::class, $repository);
        $useCase = $this->app->make(DeletePolicyInterface::class);
        $this->assertInstanceOf(DeletePolicy::class, $useCase);
    }

    /**
     * @throws BindingResolutionException
     */
    public function testProcess(): void
    {
        $policyIdentifier = new PolicyIdentifier(StrTestHelper::generateUuid());

        $policy = new Policy(
            $policyIdentifier,
            'Test Policy',
            [
                new Statement(
                    Effect::ALLOW,
                    [Action::CREATE],
                    [ResourceType::TALENT],
                    null,
                ),
            ],
            false, // isSystemPolicy = false
            new DateTimeImmutable(),
        );

        $repository = Mockery::mock(PolicyRepositoryInterface::class);
        $repository->shouldReceive('findById')
            ->once()
            ->with(Mockery::on(fn ($arg) => (string) $arg === (string) $policyIdentifier))
            ->andReturn($policy);
        $repository->shouldReceive('delete')
            ->once()
            ->with($policy)
            ->andReturnNull();

        $this->app->instance(PolicyRepositoryInterface::class, $repository);

        $useCase = $this->app->make(DeletePolicyInterface::class);
        $input = new DeletePolicyInput($policyIdentifier);

        $useCase->process($input);
    }

    /**
     * @throws BindingResolutionException
     */
    public function testThrowsWhenNotFound(): void
    {
        $policyIdentifier = new PolicyIdentifier(StrTestHelper::generateUuid());

        $repository = Mockery::mock(PolicyRepositoryInterface::class);
        $repository->shouldReceive('findById')
            ->once()
            ->with(Mockery::on(fn ($arg) => (string) $arg === (string) $policyIdentifier))
            ->andReturnNull();
        $repository->shouldNotReceive('delete');

        $this->app->instance(PolicyRepositoryInterface::class, $repository);

        $useCase = $this->app->make(DeletePolicyInterface::class);
        $input = new DeletePolicyInput($policyIdentifier);

        $this->expectException(PolicyNotFoundException::class);

        $useCase->process($input);
    }

    /**
     * @throws BindingResolutionException
     */
    public function testThrowsWhenSystemPolicy(): void
    {
        $policyIdentifier = new PolicyIdentifier(StrTestHelper::generateUuid());

        $policy = new Policy(
            $policyIdentifier,
            'System Policy',
            [
                new Statement(
                    Effect::ALLOW,
                    Action::cases(),
                    ResourceType::cases(),
                    null,
                ),
            ],
            true, // isSystemPolicy = true
            new DateTimeImmutable(),
        );

        $repository = Mockery::mock(PolicyRepositoryInterface::class);
        $repository->shouldReceive('findById')
            ->once()
            ->with(Mockery::on(fn ($arg) => (string) $arg === (string) $policyIdentifier))
            ->andReturn($policy);
        $repository->shouldNotReceive('delete');

        $this->app->instance(PolicyRepositoryInterface::class, $repository);

        $useCase = $this->app->make(DeletePolicyInterface::class);
        $input = new DeletePolicyInput($policyIdentifier);

        $this->expectException(CannotDeleteSystemPolicyException::class);

        $useCase->process($input);
    }
}
