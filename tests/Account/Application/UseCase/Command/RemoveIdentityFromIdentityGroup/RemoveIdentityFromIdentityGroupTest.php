<?php

declare(strict_types=1);

namespace Tests\Account\Application\UseCase\Command\RemoveIdentityFromIdentityGroup;

use DateTimeImmutable;
use DomainException;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Account\Application\Exception\CannotRemoveLastOwnerException;
use Source\Account\Application\Exception\IdentityGroupNotFoundException;
use Source\Account\Application\UseCase\Command\RemoveIdentityFromIdentityGroup\RemoveIdentityFromIdentityGroup;
use Source\Account\Application\UseCase\Command\RemoveIdentityFromIdentityGroup\RemoveIdentityFromIdentityGroupInput;
use Source\Account\Application\UseCase\Command\RemoveIdentityFromIdentityGroup\RemoveIdentityFromIdentityGroupInterface;
use Source\Account\Domain\Entity\IdentityGroup;
use Source\Account\Domain\Repository\IdentityGroupRepositoryInterface;
use Source\Account\Domain\ValueObject\AccountRole;
use Source\Account\Domain\ValueObject\IdentityGroupIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RemoveIdentityFromIdentityGroupTest extends TestCase
{
    /**
     * @return void
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $repository = Mockery::mock(IdentityGroupRepositoryInterface::class);
        $this->app->instance(IdentityGroupRepositoryInterface::class, $repository);
        $useCase = $this->app->make(RemoveIdentityFromIdentityGroupInterface::class);
        $this->assertInstanceOf(RemoveIdentityFromIdentityGroup::class, $useCase);
    }

    /**
     * @return void
     * @throws BindingResolutionException
     */
    public function testProcess(): void
    {
        $identityGroupIdentifier = new IdentityGroupIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());

        $identityGroup = new IdentityGroup(
            $identityGroupIdentifier,
            $accountIdentifier,
            'Test Group',
            AccountRole::MEMBER,
            false,
            new DateTimeImmutable(),
        );
        $identityGroup->addMember($identityIdentifier);

        $repository = Mockery::mock(IdentityGroupRepositoryInterface::class);
        $repository->shouldReceive('findById')
            ->once()
            ->with(Mockery::on(fn ($arg) => (string) $arg === (string) $identityGroupIdentifier))
            ->andReturn($identityGroup);
        $repository->shouldReceive('save')
            ->once()
            ->with(Mockery::on(function (IdentityGroup $arg) use ($identityIdentifier) {
                return ! $arg->hasMember($identityIdentifier);
            }))
            ->andReturnNull();

        $this->app->instance(IdentityGroupRepositoryInterface::class, $repository);

        $useCase = $this->app->make(RemoveIdentityFromIdentityGroupInterface::class);
        $input = new RemoveIdentityFromIdentityGroupInput($identityGroupIdentifier, $identityIdentifier);

        $result = $useCase->process($input);

        $this->assertFalse($result->hasMember($identityIdentifier));
    }

    /**
     * @return void
     * @throws BindingResolutionException
     */
    public function testThrowsWhenNotFound(): void
    {
        $identityGroupIdentifier = new IdentityGroupIdentifier(StrTestHelper::generateUuid());
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());

        $repository = Mockery::mock(IdentityGroupRepositoryInterface::class);
        $repository->shouldReceive('findById')
            ->once()
            ->with(Mockery::on(fn ($arg) => (string) $arg === (string) $identityGroupIdentifier))
            ->andReturnNull();
        $repository->shouldNotReceive('save');

        $this->app->instance(IdentityGroupRepositoryInterface::class, $repository);

        $useCase = $this->app->make(RemoveIdentityFromIdentityGroupInterface::class);
        $input = new RemoveIdentityFromIdentityGroupInput($identityGroupIdentifier, $identityIdentifier);

        $this->expectException(IdentityGroupNotFoundException::class);

        $useCase->process($input);
    }

    /**
     * @return void
     * @throws BindingResolutionException
     */
    public function testThrowsWhenNotMember(): void
    {
        $identityGroupIdentifier = new IdentityGroupIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());

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
        $repository->shouldNotReceive('save');

        $this->app->instance(IdentityGroupRepositoryInterface::class, $repository);

        $useCase = $this->app->make(RemoveIdentityFromIdentityGroupInterface::class);
        $input = new RemoveIdentityFromIdentityGroupInput($identityGroupIdentifier, $identityIdentifier);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Identity is not a member of this group.');

        $useCase->process($input);
    }

    /**
     * @return void
     * @throws BindingResolutionException
     */
    public function testThrowsWhenLastOwner(): void
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
            ->andReturn([$identityGroup]); // Only one OWNER group with one member
        $repository->shouldNotReceive('save');

        $this->app->instance(IdentityGroupRepositoryInterface::class, $repository);

        $useCase = $this->app->make(RemoveIdentityFromIdentityGroupInterface::class);
        $input = new RemoveIdentityFromIdentityGroupInput($identityGroupIdentifier, $identityIdentifier);

        $this->expectException(CannotRemoveLastOwnerException::class);

        $useCase->process($input);
    }

    /**
     * @return void
     * @throws BindingResolutionException
     */
    public function testCanRemoveFromOwnerGroupWhenOtherOwnersExist(): void
    {
        $identityGroupIdentifier = new IdentityGroupIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $anotherIdentityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());

        $identityGroup = new IdentityGroup(
            $identityGroupIdentifier,
            $accountIdentifier,
            'Owner Group',
            AccountRole::OWNER,
            false,
            new DateTimeImmutable(),
        );
        $identityGroup->addMember($identityIdentifier);
        $identityGroup->addMember($anotherIdentityIdentifier);

        $repository = Mockery::mock(IdentityGroupRepositoryInterface::class);
        $repository->shouldReceive('findById')
            ->once()
            ->with(Mockery::on(fn ($arg) => (string) $arg === (string) $identityGroupIdentifier))
            ->andReturn($identityGroup);
        $repository->shouldReceive('findByAccountId')
            ->once()
            ->with(Mockery::on(fn ($arg) => (string) $arg === (string) $accountIdentifier))
            ->andReturn([$identityGroup]);
        $repository->shouldReceive('save')
            ->once()
            ->andReturnNull();

        $this->app->instance(IdentityGroupRepositoryInterface::class, $repository);

        $useCase = $this->app->make(RemoveIdentityFromIdentityGroupInterface::class);
        $input = new RemoveIdentityFromIdentityGroupInput($identityGroupIdentifier, $identityIdentifier);

        $result = $useCase->process($input);

        $this->assertFalse($result->hasMember($identityIdentifier));
        $this->assertTrue($result->hasMember($anotherIdentityIdentifier));
    }
}
