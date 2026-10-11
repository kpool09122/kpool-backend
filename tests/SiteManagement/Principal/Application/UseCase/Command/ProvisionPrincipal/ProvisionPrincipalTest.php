<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Principal\Application\UseCase\Command\ProvisionPrincipal;

use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\SiteManagement\Principal\Application\UseCase\Command\ProvisionPrincipal\ProvisionPrincipal;
use Source\SiteManagement\Principal\Application\UseCase\Command\ProvisionPrincipal\ProvisionPrincipalInput;
use Source\SiteManagement\Principal\Application\UseCase\Command\ProvisionPrincipal\ProvisionPrincipalOutput;
use Source\SiteManagement\Principal\Domain\Entity\Principal;
use Source\SiteManagement\Principal\Domain\Entity\PrincipalGroup;
use Source\SiteManagement\Principal\Domain\Entity\Role;
use Source\SiteManagement\Principal\Domain\Factory\PrincipalFactoryInterface;
use Source\SiteManagement\Principal\Domain\Factory\PrincipalGroupFactoryInterface;
use Source\SiteManagement\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\SiteManagement\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\SiteManagement\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\SiteManagement\Principal\Domain\ValueObject\RoleIdentifier;

class ProvisionPrincipalTest extends TestCase
{
    public function testNewPrincipalGetsGeneralMembership(): void
    {
        $identity = new IdentityIdentifier('69200000-0000-7000-8000-000000000098');
        $principal = new Principal(new PrincipalIdentifier('69200000-0000-7000-8000-000000000099'), $identity, new AccountIdentifier('00000000-0000-7000-8000-000000000009'));
        $repository = $this->createMock(PrincipalRepositoryInterface::class);
        $repository->method('findByIdentityIdentifierAndAccountIdentifier')->willReturn(null);
        $repository->expects(self::once())->method('save')->with($principal);
        $factory = $this->createMock(PrincipalFactoryInterface::class);
        $factory->expects(self::once())->method('create')->with($identity, new AccountIdentifier('00000000-0000-7000-8000-000000000009'))->willReturn($principal);
        $groups = $this->createMock(PrincipalGroupRepositoryInterface::class);
        $group = new PrincipalGroup(new PrincipalGroupIdentifier('69200000-0000-7000-8000-000000000097'), 'General', [], new AccountIdentifier('00000000-0000-7000-8000-000000000009'));
        $groups->method('findDefaultByAccountIdentifier')->willReturn($group);
        $groups->expects(self::once())->method('save')->with($group);
        $output = new ProvisionPrincipalOutput();
        (new ProvisionPrincipal($repository, $factory, $groups, $this->createMock(PrincipalGroupFactoryInterface::class), $this->createMock(RoleRepositoryInterface::class)))->process(new ProvisionPrincipalInput($identity, new AccountIdentifier('00000000-0000-7000-8000-000000000009')), $output);
        self::assertSame($principal, $output->principal());
        self::assertSame([$principal->principalIdentifier()], array_values($group->members()));
    }

    public function testNewDefaultGroupUsesSystemRoleResolvedByName(): void
    {
        $identity = new IdentityIdentifier('69200000-0000-7000-8000-000000000098');
        $account = new AccountIdentifier('00000000-0000-7000-8000-000000000009');
        $principal = new Principal(new PrincipalIdentifier('69200000-0000-7000-8000-000000000099'), $identity, $account);
        $role = new Role(new RoleIdentifier('69200000-0000-7000-8000-000000000096'), 'General', []);
        $group = new PrincipalGroup(new PrincipalGroupIdentifier('69200000-0000-7000-8000-000000000097'), 'General', [$role->roleIdentifier()], $account, true);
        $principalRepository = $this->createMock(PrincipalRepositoryInterface::class);
        $principalRepository->expects(self::once())->method('save')->with($principal);
        $principalFactory = $this->createMock(PrincipalFactoryInterface::class);
        $principalFactory->expects(self::once())->method('create')->with($identity, $account)->willReturn($principal);
        $principalGroupRepository = $this->createMock(PrincipalGroupRepositoryInterface::class);
        $principalGroupRepository->expects(self::once())->method('save')->with($group);
        $principalGroupFactory = $this->createMock(PrincipalGroupFactoryInterface::class);
        $principalGroupFactory->expects(self::once())->method('create')
            ->with('General', [$role->roleIdentifier()], $account, true)->willReturn($group);
        $roleRepository = $this->createMock(RoleRepositoryInterface::class);
        $roleRepository->expects(self::once())->method('findSystemByName')->with('General')->willReturn($role);
        $output = new ProvisionPrincipalOutput();
        (new ProvisionPrincipal($principalRepository, $principalFactory, $principalGroupRepository, $principalGroupFactory, $roleRepository))
            ->process(new ProvisionPrincipalInput($identity, $account), $output);

        self::assertSame($principal, $output->principal());
        self::assertSame([$principal->principalIdentifier()], array_values($group->members()));
    }

    public function testExistingPrincipalIsReturnedWithoutCreatingOrChangingMembership(): void
    {
        $identity = new IdentityIdentifier('69200000-0000-7000-8000-000000000098');
        $principal = new Principal(new PrincipalIdentifier('69200000-0000-7000-8000-000000000099'), $identity, new AccountIdentifier('00000000-0000-7000-8000-000000000009'));
        $repository = $this->createMock(PrincipalRepositoryInterface::class);
        $repository->method('findByIdentityIdentifierAndAccountIdentifier')->willReturn($principal);
        $repository->expects(self::never())->method('save');
        $factory = $this->createMock(PrincipalFactoryInterface::class);
        $factory->expects(self::never())->method('create');
        $groups = $this->createMock(PrincipalGroupRepositoryInterface::class);
        $groups->expects(self::never())->method('save');
        $output = new ProvisionPrincipalOutput();
        (new ProvisionPrincipal($repository, $factory, $groups, $this->createMock(PrincipalGroupFactoryInterface::class), $this->createMock(RoleRepositoryInterface::class)))->process(new ProvisionPrincipalInput($identity, new AccountIdentifier('00000000-0000-7000-8000-000000000009')), $output);
        self::assertSame($principal, $output->principal());
    }
}
