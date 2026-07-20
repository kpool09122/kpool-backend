<?php

declare(strict_types=1);

namespace Tests\Account\PrincipalGroup\Application\UseCase\Command\RemovePrincipalFromPrincipalGroup;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Account\Principal\Application\Exception\CannotRemoveLastOwnerException;
use Source\Account\Principal\Application\Exception\PrincipalGroupNotFoundException;
use Source\Account\Principal\Application\UseCase\Command\RemovePrincipalFromPrincipalGroup\RemovePrincipalFromPrincipalGroup;
use Source\Account\Principal\Application\UseCase\Command\RemovePrincipalFromPrincipalGroup\RemovePrincipalFromPrincipalGroupInput;
use Source\Account\Principal\Application\UseCase\Command\RemovePrincipalFromPrincipalGroup\RemovePrincipalFromPrincipalGroupInterface;
use Source\Account\Principal\Application\UseCase\Command\RemovePrincipalFromPrincipalGroup\RemovePrincipalFromPrincipalGroupOutput;
use Source\Account\Principal\Domain\Entity\PrincipalGroup;
use Source\Account\Principal\Domain\Exception\PrincipalNotMemberException;
use Source\Account\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Account\Principal\Domain\ValueObject\AccountRole;
use Source\Account\Shared\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
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
        $principalIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());

        $principalGroup = new PrincipalGroup(
            $principalGroupIdentifier,
            $accountIdentifier,
            'Test Group',
            AccountRole::MEMBER,
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
        $this->assertNotContains((string) $principalIdentifier, $result['members']);
    }

    /**
     * @return void
     * @throws BindingResolutionException
     */
    public function testThrowsWhenNotFound(): void
    {
        $principalGroupIdentifier = new PrincipalGroupIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());

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
        $principalIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());

        $principalGroup = new PrincipalGroup(
            $principalGroupIdentifier,
            $accountIdentifier,
            'Test Group',
            AccountRole::MEMBER,
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

        $output = new RemovePrincipalFromPrincipalGroupOutput();
        $useCase->process($input, $output);
    }

    /**
     * @return void
     * @throws BindingResolutionException
     */
    public function testThrowsWhenLastOwner(): void
    {
        $principalGroupIdentifier = new PrincipalGroupIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());

        $principalGroup = new PrincipalGroup(
            $principalGroupIdentifier,
            $accountIdentifier,
            'Owner Group',
            AccountRole::OWNER,
            false,
            new DateTimeImmutable(),
        );
        $principalGroup->addMember($principalIdentifier);

        $repository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $repository->shouldReceive('findById')
            ->once()
            ->with(Mockery::on(fn ($arg) => (string) $arg === (string) $principalGroupIdentifier))
            ->andReturn($principalGroup);
        $repository->shouldReceive('findByAccountId')
            ->once()
            ->with(Mockery::on(fn ($arg) => (string) $arg === (string) $accountIdentifier))
            ->andReturn([$principalGroup]); // Only one OWNER group with one member
        $repository->shouldNotReceive('save');

        $this->app->instance(PrincipalGroupRepositoryInterface::class, $repository);

        $useCase = $this->app->make(RemovePrincipalFromPrincipalGroupInterface::class);
        $input = new RemovePrincipalFromPrincipalGroupInput($principalGroupIdentifier, $principalIdentifier);

        $this->expectException(CannotRemoveLastOwnerException::class);

        $output = new RemovePrincipalFromPrincipalGroupOutput();
        $useCase->process($input, $output);
    }

    /**
     * @return void
     * @throws BindingResolutionException
     */
    public function testCanRemoveFromOwnerGroupWhenOtherOwnersExist(): void
    {
        $principalGroupIdentifier = new PrincipalGroupIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $anotherPrincipalIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());

        $principalGroup = new PrincipalGroup(
            $principalGroupIdentifier,
            $accountIdentifier,
            'Owner Group',
            AccountRole::OWNER,
            false,
            new DateTimeImmutable(),
        );
        $principalGroup->addMember($principalIdentifier);
        $principalGroup->addMember($anotherPrincipalIdentifier);

        $repository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $repository->shouldReceive('findById')
            ->once()
            ->with(Mockery::on(fn ($arg) => (string) $arg === (string) $principalGroupIdentifier))
            ->andReturn($principalGroup);
        $repository->shouldReceive('findByAccountId')
            ->once()
            ->with(Mockery::on(fn ($arg) => (string) $arg === (string) $accountIdentifier))
            ->andReturn([$principalGroup]);
        $repository->shouldReceive('save')
            ->once()
            ->andReturnNull();

        $this->app->instance(PrincipalGroupRepositoryInterface::class, $repository);

        $useCase = $this->app->make(RemovePrincipalFromPrincipalGroupInterface::class);
        $input = new RemovePrincipalFromPrincipalGroupInput($principalGroupIdentifier, $principalIdentifier);

        $output = new RemovePrincipalFromPrincipalGroupOutput();
        $useCase->process($input, $output);

        $result = $output->toArray();
        $this->assertNotContains((string) $principalIdentifier, $result['members']);
        $this->assertContains((string) $anotherPrincipalIdentifier, $result['members']);
    }
}
