<?php

declare(strict_types=1);

namespace Tests\Account\Account\Infrastructure\Service;

use Mockery;
use Mockery\MockInterface;
use Source\Account\Account\Domain\Exception\IdentityWithdrawalNotAllowedException;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Account\Infrastructure\Service\WithdrawalEligibilityService;
use Source\Account\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Account\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Account\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\TestCase;

class WithdrawalEligibilityServiceTest extends TestCase
{
    public function testMissingMembershipIsRejectedWithoutAccessingAccountsOrRoles(): void
    {
        $identityIdentifier = new IdentityIdentifier('123e4567-e89b-72d3-a456-426614174001');
        /** @var PrincipalRepositoryInterface&MockInterface $principalRepository */
        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findAllByIdentityIdentifier')->once()->with($identityIdentifier)->andReturn([]);
        /** @var AccountRepositoryInterface&MockInterface $accountRepository */
        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        $accountRepository->shouldNotReceive('findByIds');
        /** @var PrincipalGroupRepositoryInterface&MockInterface $principalGroupRepository */
        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $principalGroupRepository->shouldNotReceive('findByPrincipalIds');
        /** @var RoleRepositoryInterface&MockInterface $roleRepository */
        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);
        $roleRepository->shouldNotReceive('findSystemByName');

        /** @var IdentityRepositoryInterface&MockInterface $identityRepository */
        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        $identityRepository->shouldNotReceive('findById');

        $this->expectException(IdentityWithdrawalNotAllowedException::class);
        (new WithdrawalEligibilityService($accountRepository, $principalRepository, $principalGroupRepository, $roleRepository, $identityRepository))->resolve($identityIdentifier);
    }
}
