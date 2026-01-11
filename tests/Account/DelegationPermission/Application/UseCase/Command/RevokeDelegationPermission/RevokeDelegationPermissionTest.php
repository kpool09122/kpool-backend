<?php

declare(strict_types=1);

namespace Tests\Account\DelegationPermission\Application\UseCase\Command\RevokeDelegationPermission;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Account\DelegationPermission\Application\Exception\DelegationPermissionNotFoundException;
use Source\Account\DelegationPermission\Application\UseCase\Command\RevokeDelegationPermission\RevokeDelegationPermission;
use Source\Account\DelegationPermission\Application\UseCase\Command\RevokeDelegationPermission\RevokeDelegationPermissionInput;
use Source\Account\DelegationPermission\Application\UseCase\Command\RevokeDelegationPermission\RevokeDelegationPermissionInterface;
use Source\Account\DelegationPermission\Domain\Entity\DelegationPermission;
use Source\Account\DelegationPermission\Domain\Repository\DelegationPermissionRepositoryInterface;
use Source\Account\DelegationPermission\Domain\ValueObject\DelegationPermissionIdentifier;
use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Account\Shared\Domain\ValueObject\IdentityGroupIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RevokeDelegationPermissionTest extends TestCase
{
    /**
     * @return void
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $repository = Mockery::mock(DelegationPermissionRepositoryInterface::class);
        $this->app->instance(DelegationPermissionRepositoryInterface::class, $repository);
        $useCase = $this->app->make(RevokeDelegationPermissionInterface::class);
        $this->assertInstanceOf(RevokeDelegationPermission::class, $useCase);
    }

    /**
     * @return void
     * @throws BindingResolutionException
     */
    public function testProcess(): void
    {
        $delegationPermissionIdentifier = new DelegationPermissionIdentifier(StrTestHelper::generateUuid());
        $identityGroupIdentifier = new IdentityGroupIdentifier(StrTestHelper::generateUuid());
        $targetAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $affiliationIdentifier = new AffiliationIdentifier(StrTestHelper::generateUuid());

        $delegationPermission = new DelegationPermission(
            $delegationPermissionIdentifier,
            $identityGroupIdentifier,
            $targetAccountIdentifier,
            $affiliationIdentifier,
            new DateTimeImmutable(),
        );

        $repository = Mockery::mock(DelegationPermissionRepositoryInterface::class);
        $repository->shouldReceive('findById')
            ->once()
            ->with(Mockery::on(fn ($arg) => (string) $arg === (string) $delegationPermissionIdentifier))
            ->andReturn($delegationPermission);
        $repository->shouldReceive('delete')
            ->once()
            ->with($delegationPermission)
            ->andReturnNull();

        $this->app->instance(DelegationPermissionRepositoryInterface::class, $repository);

        $useCase = $this->app->make(RevokeDelegationPermissionInterface::class);
        $input = new RevokeDelegationPermissionInput($delegationPermissionIdentifier);

        $useCase->process($input);
    }

    /**
     * @return void
     * @throws BindingResolutionException
     */
    public function testThrowsWhenNotFound(): void
    {
        $delegationPermissionIdentifier = new DelegationPermissionIdentifier(StrTestHelper::generateUuid());

        $repository = Mockery::mock(DelegationPermissionRepositoryInterface::class);
        $repository->shouldReceive('findById')
            ->once()
            ->with(Mockery::on(fn ($arg) => (string) $arg === (string) $delegationPermissionIdentifier))
            ->andReturnNull();
        $repository->shouldNotReceive('delete');

        $this->app->instance(DelegationPermissionRepositoryInterface::class, $repository);

        $useCase = $this->app->make(RevokeDelegationPermissionInterface::class);
        $input = new RevokeDelegationPermissionInput($delegationPermissionIdentifier);

        $this->expectException(DelegationPermissionNotFoundException::class);

        $useCase->process($input);
    }
}
