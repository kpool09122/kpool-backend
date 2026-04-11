<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Application\UseCase\Command\RemovePrincipalFromPrincipalGroup;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Wiki\Principal\Application\Exception\PrincipalGroupNotFoundException;
use Source\Wiki\Principal\Application\UseCase\Command\RemovePrincipalFromPrincipalGroup\RemovePrincipalFromPrincipalGroup;
use Source\Wiki\Principal\Application\UseCase\Command\RemovePrincipalFromPrincipalGroup\RemovePrincipalFromPrincipalGroupInput;
use Source\Wiki\Principal\Application\UseCase\Command\RemovePrincipalFromPrincipalGroup\RemovePrincipalFromPrincipalGroupInterface;
use Source\Wiki\Principal\Application\UseCase\Command\RemovePrincipalFromPrincipalGroup\RemovePrincipalFromPrincipalGroupOutput;
use Source\Wiki\Principal\Domain\Entity\PrincipalGroup;
use Source\Wiki\Principal\Domain\Exception\PrincipalNotMemberException;
use Source\Wiki\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Wiki\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RemovePrincipalFromPrincipalGroupTest extends TestCase
{
    /**
     * @return void
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $repository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $this->app->instance(PrincipalGroupRepositoryInterface::class, $repository);
        $useCase = $this->app->make(RemovePrincipalFromPrincipalGroupInterface::class);
        $this->assertInstanceOf(RemovePrincipalFromPrincipalGroup::class, $useCase);
    }

    /**
     * @return void
     * @throws BindingResolutionException
     */
    public function testProcess(): void
    {
        $principalGroupIdentifier = new PrincipalGroupIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $principalGroup = new PrincipalGroup(
            $principalGroupIdentifier,
            $accountIdentifier,
            'Test Group',
            false,
            new DateTimeImmutable(),
        );
        $principalGroup->addMember($principalIdentifier);

        $repository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $repository->shouldReceive('findById')
            ->once()
            ->with(Mockery::on(fn ($arg) => (string) $arg === (string) $principalGroupIdentifier))
            ->andReturn($principalGroup);
        $repository->shouldReceive('save')
            ->once()
            ->with(Mockery::on(fn (PrincipalGroup $arg) => ! $arg->hasMember($principalIdentifier)))
            ->andReturnNull();

        $this->app->instance(PrincipalGroupRepositoryInterface::class, $repository);

        $useCase = $this->app->make(RemovePrincipalFromPrincipalGroupInterface::class);
        $input = new RemovePrincipalFromPrincipalGroupInput($principalGroupIdentifier, $principalIdentifier);
        $output = new RemovePrincipalFromPrincipalGroupOutput();

        $useCase->process($input, $output);

        $result = $output->toArray();
        $this->assertSame((string) $principalGroupIdentifier, $result['principalGroupIdentifier']);
    }

    /**
     * @return void
     * @throws BindingResolutionException
     */
    public function testThrowsWhenNotFound(): void
    {
        $principalGroupIdentifier = new PrincipalGroupIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $repository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $repository->shouldReceive('findById')
            ->once()
            ->with(Mockery::on(fn ($arg) => (string) $arg === (string) $principalGroupIdentifier))
            ->andReturnNull();
        $repository->shouldNotReceive('save');

        $this->app->instance(PrincipalGroupRepositoryInterface::class, $repository);

        $useCase = $this->app->make(RemovePrincipalFromPrincipalGroupInterface::class);
        $input = new RemovePrincipalFromPrincipalGroupInput($principalGroupIdentifier, $principalIdentifier);

        $this->expectException(PrincipalGroupNotFoundException::class);

        $output = new RemovePrincipalFromPrincipalGroupOutput();
        $useCase->process($input, $output);
    }

    /**
     * @return void
     * @throws BindingResolutionException
     */
    public function testThrowsWhenNotMember(): void
    {
        $principalGroupIdentifier = new PrincipalGroupIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $principalGroup = new PrincipalGroup(
            $principalGroupIdentifier,
            $accountIdentifier,
            'Test Group',
            false,
            new DateTimeImmutable(),
        );

        $repository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $repository->shouldReceive('findById')
            ->once()
            ->with(Mockery::on(fn ($arg) => (string) $arg === (string) $principalGroupIdentifier))
            ->andReturn($principalGroup);
        $repository->shouldNotReceive('save');

        $this->app->instance(PrincipalGroupRepositoryInterface::class, $repository);

        $useCase = $this->app->make(RemovePrincipalFromPrincipalGroupInterface::class);
        $input = new RemovePrincipalFromPrincipalGroupInput($principalGroupIdentifier, $principalIdentifier);

        $this->expectException(PrincipalNotMemberException::class);
        $this->expectExceptionMessage('Principal is not a member of this group.');

        $output = new RemovePrincipalFromPrincipalGroupOutput();
        $useCase->process($input, $output);
    }
}
