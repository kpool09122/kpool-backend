<?php

declare(strict_types=1);

namespace Tests\Account\Application\UseCase\Command\DeleteIdentityGroup;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Account\Application\Exception\CannotDeleteDefaultIdentityGroupException;
use Source\Account\Application\Exception\CannotDeleteLastOwnerGroupException;
use Source\Account\Application\Exception\IdentityGroupNotFoundException;
use Source\Account\Application\UseCase\Command\DeleteIdentityGroup\DeleteIdentityGroup;
use Source\Account\Application\UseCase\Command\DeleteIdentityGroup\DeleteIdentityGroupInput;
use Source\Account\Application\UseCase\Command\DeleteIdentityGroup\DeleteIdentityGroupInterface;
use Source\Account\Domain\Entity\IdentityGroup;
use Source\Account\Domain\Repository\IdentityGroupRepositoryInterface;
use Source\Account\Domain\ValueObject\AccountRole;
use Source\Account\Domain\ValueObject\IdentityGroupIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class DeleteIdentityGroupTest extends TestCase
{
    /**
     * @return void
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $repository = Mockery::mock(IdentityGroupRepositoryInterface::class);
        $this->app->instance(IdentityGroupRepositoryInterface::class, $repository);
        $useCase = $this->app->make(DeleteIdentityGroupInterface::class);
        $this->assertInstanceOf(DeleteIdentityGroup::class, $useCase);
    }

    /**
     * @return void
     * @throws BindingResolutionException
     */
    public function testProcess(): void
    {
        $identityGroupIdentifier = new IdentityGroupIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());

        $identityGroup = new IdentityGroup(
            $identityGroupIdentifier,
            $accountIdentifier,
            'Test Group',
            AccountRole::MEMBER,
            false,
            new DateTimeImmutable(),
        );

        $repository = Mockery::mock(IdentityGroupRepositoryInterface::class);
        $repository->shouldReceive('findById')
            ->once()
            ->with(Mockery::on(fn ($arg) => (string) $arg === (string) $identityGroupIdentifier))
            ->andReturn($identityGroup);
        $repository->shouldReceive('delete')
            ->once()
            ->with($identityGroup)
            ->andReturnNull();

        $this->app->instance(IdentityGroupRepositoryInterface::class, $repository);

        $useCase = $this->app->make(DeleteIdentityGroupInterface::class);
        $input = new DeleteIdentityGroupInput($identityGroupIdentifier);

        $useCase->process($input);
    }

    /**
     * @return void
     * @throws BindingResolutionException
     */
    public function testThrowsWhenNotFound(): void
    {
        $identityGroupIdentifier = new IdentityGroupIdentifier(StrTestHelper::generateUuid());

        $repository = Mockery::mock(IdentityGroupRepositoryInterface::class);
        $repository->shouldReceive('findById')
            ->once()
            ->with(Mockery::on(fn ($arg) => (string) $arg === (string) $identityGroupIdentifier))
            ->andReturnNull();
        $repository->shouldNotReceive('delete');

        $this->app->instance(IdentityGroupRepositoryInterface::class, $repository);

        $useCase = $this->app->make(DeleteIdentityGroupInterface::class);
        $input = new DeleteIdentityGroupInput($identityGroupIdentifier);

        $this->expectException(IdentityGroupNotFoundException::class);

        $useCase->process($input);
    }

    /**
     * @return void
     * @throws BindingResolutionException
     */
    public function testThrowsWhenDefaultGroup(): void
    {
        $identityGroupIdentifier = new IdentityGroupIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());

        $identityGroup = new IdentityGroup(
            $identityGroupIdentifier,
            $accountIdentifier,
            'Default Owner Group',
            AccountRole::OWNER,
            true, // isDefault = true
            new DateTimeImmutable(),
        );

        $repository = Mockery::mock(IdentityGroupRepositoryInterface::class);
        $repository->shouldReceive('findById')
            ->once()
            ->with(Mockery::on(fn ($arg) => (string) $arg === (string) $identityGroupIdentifier))
            ->andReturn($identityGroup);
        $repository->shouldNotReceive('delete');

        $this->app->instance(IdentityGroupRepositoryInterface::class, $repository);

        $useCase = $this->app->make(DeleteIdentityGroupInterface::class);
        $input = new DeleteIdentityGroupInput($identityGroupIdentifier);

        $this->expectException(CannotDeleteDefaultIdentityGroupException::class);

        $useCase->process($input);
    }

    /**
     * @return void
     * @throws BindingResolutionException
     */
    public function testThrowsWhenLastOwnerGroup(): void
    {
        $identityGroupIdentifier = new IdentityGroupIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());

        $identityGroup = new IdentityGroup(
            $identityGroupIdentifier,
            $accountIdentifier,
            'Owner Group',
            AccountRole::OWNER,
            false,
            new DateTimeImmutable(),
        );
        $identityGroup->addMember($identityIdentifier);

        $repository = Mockery::mock(IdentityGroupRepositoryInterface::class);
        $repository->shouldReceive('findById')
            ->once()
            ->with(Mockery::on(fn ($arg) => (string) $arg === (string) $identityGroupIdentifier))
            ->andReturn($identityGroup);
        $repository->shouldReceive('findByAccountId')
            ->once()
            ->with(Mockery::on(fn ($arg) => (string) $arg === (string) $accountIdentifier))
            ->andReturn([$identityGroup]); // Only one OWNER group with members
        $repository->shouldNotReceive('delete');

        $this->app->instance(IdentityGroupRepositoryInterface::class, $repository);

        $useCase = $this->app->make(DeleteIdentityGroupInterface::class);
        $input = new DeleteIdentityGroupInput($identityGroupIdentifier);

        $this->expectException(CannotDeleteLastOwnerGroupException::class);

        $useCase->process($input);
    }
}
