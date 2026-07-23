<?php

declare(strict_types=1);

namespace Tests\Account\DelegationPermission\Application\UseCase\Command\GrantDelegationPermission;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Account\DelegationPermission\Application\UseCase\Command\GrantDelegationPermission\GrantDelegationPermission;
use Source\Account\DelegationPermission\Application\UseCase\Command\GrantDelegationPermission\GrantDelegationPermissionInput;
use Source\Account\DelegationPermission\Application\UseCase\Command\GrantDelegationPermission\GrantDelegationPermissionInterface;
use Source\Account\DelegationPermission\Application\UseCase\Command\GrantDelegationPermission\GrantDelegationPermissionOutput;
use Source\Account\DelegationPermission\Domain\Entity\DelegationPermission;
use Source\Account\DelegationPermission\Domain\Factory\DelegationPermissionFactoryInterface;
use Source\Account\DelegationPermission\Domain\Repository\DelegationPermissionRepositoryInterface;
use Source\Account\DelegationPermission\Domain\ValueObject\DelegationPermissionIdentifier;
use Source\Account\Principal\Application\Exception\PrincipalGroupNotFoundException;
use Source\Account\Principal\Domain\Entity\PrincipalGroup;
use Source\Account\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Account\Principal\Domain\ValueObject\AccountRole;
use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Account\Shared\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class GrantDelegationPermissionTest extends TestCase
{
    /**
     * @return void
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $delegationPermissionRepository = Mockery::mock(DelegationPermissionRepositoryInterface::class);
        $factory = Mockery::mock(DelegationPermissionFactoryInterface::class);
        $this->app->instance(PrincipalGroupRepositoryInterface::class, $principalGroupRepository);
        $this->app->instance(DelegationPermissionRepositoryInterface::class, $delegationPermissionRepository);
        $this->app->instance(DelegationPermissionFactoryInterface::class, $factory);
        $useCase = $this->app->make(GrantDelegationPermissionInterface::class);
        $this->assertInstanceOf(GrantDelegationPermission::class, $useCase);
    }

    /**
     * @return void
     * @throws BindingResolutionException
     */
    public function testProcess(): void
    {
        $principalGroupIdentifier = new PrincipalGroupIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $targetAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $affiliationIdentifier = new AffiliationIdentifier(StrTestHelper::generateUuid());
        $delegationPermissionIdentifier = new DelegationPermissionIdentifier(StrTestHelper::generateUuid());

        $principalGroup = new PrincipalGroup(
            $principalGroupIdentifier,
            $accountIdentifier,
            'Test Group',
            AccountRole::MEMBER,
            false,
            new DateTimeImmutable(),
        );

        $delegationPermission = new DelegationPermission(
            $delegationPermissionIdentifier,
            $principalGroupIdentifier,
            $targetAccountIdentifier,
            $affiliationIdentifier,
            new DateTimeImmutable(),
        );

        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $principalGroupRepository->shouldReceive('findById')
            ->once()
            ->with(Mockery::on(fn ($arg) => (string) $arg === (string) $principalGroupIdentifier))
            ->andReturn($principalGroup);

        $delegationPermissionRepository = Mockery::mock(DelegationPermissionRepositoryInterface::class);
        $delegationPermissionRepository->shouldReceive('save')
            ->once()
            ->with($delegationPermission)
            ->andReturnNull();

        $factory = Mockery::mock(DelegationPermissionFactoryInterface::class);
        $factory->shouldReceive('create')
            ->once()
            ->with(
                Mockery::on(fn ($arg) => (string) $arg === (string) $principalGroupIdentifier),
                Mockery::on(fn ($arg) => (string) $arg === (string) $targetAccountIdentifier),
                Mockery::on(fn ($arg) => (string) $arg === (string) $affiliationIdentifier),
            )
            ->andReturn($delegationPermission);

        $this->app->instance(PrincipalGroupRepositoryInterface::class, $principalGroupRepository);
        $this->app->instance(DelegationPermissionRepositoryInterface::class, $delegationPermissionRepository);
        $this->app->instance(DelegationPermissionFactoryInterface::class, $factory);

        $useCase = $this->app->make(GrantDelegationPermissionInterface::class);
        $input = new GrantDelegationPermissionInput(
            $principalGroupIdentifier,
            $targetAccountIdentifier,
            $affiliationIdentifier,
        );

        $output = new GrantDelegationPermissionOutput();

        $useCase->process($input, $output);

        $this->assertSame((string) $delegationPermission->delegationPermissionIdentifier(), $output->toArray()['delegationPermissionIdentifier']);
    }

    /**
     * @return void
     * @throws BindingResolutionException
     */
    public function testThrowsWhenPrincipalGroupNotFound(): void
    {
        $principalGroupIdentifier = new PrincipalGroupIdentifier(StrTestHelper::generateUuid());
        $targetAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $affiliationIdentifier = new AffiliationIdentifier(StrTestHelper::generateUuid());

        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $principalGroupRepository->shouldReceive('findById')
            ->once()
            ->with(Mockery::on(fn ($arg) => (string) $arg === (string) $principalGroupIdentifier))
            ->andReturnNull();

        $delegationPermissionRepository = Mockery::mock(DelegationPermissionRepositoryInterface::class);
        $delegationPermissionRepository->shouldNotReceive('save');

        $factory = Mockery::mock(DelegationPermissionFactoryInterface::class);
        $factory->shouldNotReceive('create');

        $this->app->instance(PrincipalGroupRepositoryInterface::class, $principalGroupRepository);
        $this->app->instance(DelegationPermissionRepositoryInterface::class, $delegationPermissionRepository);
        $this->app->instance(DelegationPermissionFactoryInterface::class, $factory);

        $useCase = $this->app->make(GrantDelegationPermissionInterface::class);
        $input = new GrantDelegationPermissionInput(
            $principalGroupIdentifier,
            $targetAccountIdentifier,
            $affiliationIdentifier,
        );

        $output = new GrantDelegationPermissionOutput();

        $this->expectException(PrincipalGroupNotFoundException::class);

        $useCase->process($input, $output);
    }
}
