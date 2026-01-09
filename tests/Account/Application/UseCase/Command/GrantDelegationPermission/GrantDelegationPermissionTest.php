<?php

declare(strict_types=1);

namespace Tests\Account\Application\UseCase\Command\GrantDelegationPermission;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Account\Application\Exception\IdentityGroupNotFoundException;
use Source\Account\Application\UseCase\Command\GrantDelegationPermission\GrantDelegationPermission;
use Source\Account\Application\UseCase\Command\GrantDelegationPermission\GrantDelegationPermissionInput;
use Source\Account\Application\UseCase\Command\GrantDelegationPermission\GrantDelegationPermissionInterface;
use Source\Account\Domain\Entity\DelegationPermission;
use Source\Account\Domain\Entity\IdentityGroup;
use Source\Account\Domain\Factory\DelegationPermissionFactoryInterface;
use Source\Account\Domain\Repository\DelegationPermissionRepositoryInterface;
use Source\Account\Domain\Repository\IdentityGroupRepositoryInterface;
use Source\Account\Domain\ValueObject\AccountRole;
use Source\Account\Domain\ValueObject\AffiliationIdentifier;
use Source\Account\Domain\ValueObject\DelegationPermissionIdentifier;
use Source\Account\Domain\ValueObject\IdentityGroupIdentifier;
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
        $identityGroupRepository = Mockery::mock(IdentityGroupRepositoryInterface::class);
        $delegationPermissionRepository = Mockery::mock(DelegationPermissionRepositoryInterface::class);
        $factory = Mockery::mock(DelegationPermissionFactoryInterface::class);
        $this->app->instance(IdentityGroupRepositoryInterface::class, $identityGroupRepository);
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
        $identityGroupIdentifier = new IdentityGroupIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $targetAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $affiliationIdentifier = new AffiliationIdentifier(StrTestHelper::generateUuid());
        $delegationPermissionIdentifier = new DelegationPermissionIdentifier(StrTestHelper::generateUuid());

        $identityGroup = new IdentityGroup(
            $identityGroupIdentifier,
            $accountIdentifier,
            'Test Group',
            AccountRole::MEMBER,
            false,
            new DateTimeImmutable(),
        );

        $delegationPermission = new DelegationPermission(
            $delegationPermissionIdentifier,
            $identityGroupIdentifier,
            $targetAccountIdentifier,
            $affiliationIdentifier,
            new DateTimeImmutable(),
        );

        $identityGroupRepository = Mockery::mock(IdentityGroupRepositoryInterface::class);
        $identityGroupRepository->shouldReceive('findById')
            ->once()
            ->with(Mockery::on(fn ($arg) => (string) $arg === (string) $identityGroupIdentifier))
            ->andReturn($identityGroup);

        $delegationPermissionRepository = Mockery::mock(DelegationPermissionRepositoryInterface::class);
        $delegationPermissionRepository->shouldReceive('save')
            ->once()
            ->with($delegationPermission)
            ->andReturnNull();

        $factory = Mockery::mock(DelegationPermissionFactoryInterface::class);
        $factory->shouldReceive('create')
            ->once()
            ->with(
                Mockery::on(fn ($arg) => (string) $arg === (string) $identityGroupIdentifier),
                Mockery::on(fn ($arg) => (string) $arg === (string) $targetAccountIdentifier),
                Mockery::on(fn ($arg) => (string) $arg === (string) $affiliationIdentifier),
            )
            ->andReturn($delegationPermission);

        $this->app->instance(IdentityGroupRepositoryInterface::class, $identityGroupRepository);
        $this->app->instance(DelegationPermissionRepositoryInterface::class, $delegationPermissionRepository);
        $this->app->instance(DelegationPermissionFactoryInterface::class, $factory);

        $useCase = $this->app->make(GrantDelegationPermissionInterface::class);
        $input = new GrantDelegationPermissionInput(
            $identityGroupIdentifier,
            $targetAccountIdentifier,
            $affiliationIdentifier,
        );

        $result = $useCase->process($input);

        $this->assertSame((string) $delegationPermissionIdentifier, (string) $result->delegationPermissionIdentifier());
        $this->assertSame((string) $identityGroupIdentifier, (string) $result->identityGroupIdentifier());
        $this->assertSame((string) $targetAccountIdentifier, (string) $result->targetAccountIdentifier());
        $this->assertSame((string) $affiliationIdentifier, (string) $result->affiliationIdentifier());
    }

    /**
     * @return void
     * @throws BindingResolutionException
     */
    public function testThrowsWhenIdentityGroupNotFound(): void
    {
        $identityGroupIdentifier = new IdentityGroupIdentifier(StrTestHelper::generateUuid());
        $targetAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $affiliationIdentifier = new AffiliationIdentifier(StrTestHelper::generateUuid());

        $identityGroupRepository = Mockery::mock(IdentityGroupRepositoryInterface::class);
        $identityGroupRepository->shouldReceive('findById')
            ->once()
            ->with(Mockery::on(fn ($arg) => (string) $arg === (string) $identityGroupIdentifier))
            ->andReturnNull();

        $delegationPermissionRepository = Mockery::mock(DelegationPermissionRepositoryInterface::class);
        $delegationPermissionRepository->shouldNotReceive('save');

        $factory = Mockery::mock(DelegationPermissionFactoryInterface::class);
        $factory->shouldNotReceive('create');

        $this->app->instance(IdentityGroupRepositoryInterface::class, $identityGroupRepository);
        $this->app->instance(DelegationPermissionRepositoryInterface::class, $delegationPermissionRepository);
        $this->app->instance(DelegationPermissionFactoryInterface::class, $factory);

        $useCase = $this->app->make(GrantDelegationPermissionInterface::class);
        $input = new GrantDelegationPermissionInput(
            $identityGroupIdentifier,
            $targetAccountIdentifier,
            $affiliationIdentifier,
        );

        $this->expectException(IdentityGroupNotFoundException::class);

        $useCase->process($input);
    }
}
