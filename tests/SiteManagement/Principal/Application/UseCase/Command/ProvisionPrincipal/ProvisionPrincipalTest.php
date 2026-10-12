<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Principal\Application\UseCase\Command\ProvisionPrincipal;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Source\Account\Principal\Domain\Entity\Principal as AccountPrincipal;
use Source\Account\Principal\Domain\Entity\PrincipalGroup as AccountPrincipalGroup;
use Source\Account\Principal\Domain\Entity\Role as AccountRole;
use Source\Account\Principal\Domain\Repository\PrincipalGroupRepositoryInterface as AccountPrincipalGroupRepositoryInterface;
use Source\Account\Principal\Domain\Repository\PrincipalRepositoryInterface as AccountPrincipalRepositoryInterface;
use Source\Account\Principal\Domain\Repository\RoleRepositoryInterface as AccountRoleRepositoryInterface;
use Source\Account\Principal\Domain\ValueObject\RoleIdentifier as AccountRoleIdentifier;
use Source\Account\Shared\Domain\ValueObject\PrincipalGroupIdentifier as AccountPrincipalGroupIdentifier;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier as AccountPrincipalIdentifier;
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
        (new ProvisionPrincipal($repository, $factory, $groups, $this->createMock(PrincipalGroupFactoryInterface::class), $this->createMock(RoleRepositoryInterface::class), $this->createMock(AccountPrincipalRepositoryInterface::class), $this->createMock(AccountPrincipalGroupRepositoryInterface::class), $this->createMock(AccountRoleRepositoryInterface::class)))->process(new ProvisionPrincipalInput($identity, new AccountIdentifier('00000000-0000-7000-8000-000000000009')), $output);
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
        (new ProvisionPrincipal($principalRepository, $principalFactory, $principalGroupRepository, $principalGroupFactory, $roleRepository, $this->createMock(AccountPrincipalRepositoryInterface::class), $this->createMock(AccountPrincipalGroupRepositoryInterface::class), $this->createMock(AccountRoleRepositoryInterface::class)))
            ->process(new ProvisionPrincipalInput($identity, $account), $output);

        self::assertSame($principal, $output->principal());
        self::assertSame([$principal->principalIdentifier()], array_values($group->members()));
    }

    #[DataProvider('defaultGroupStates')]
    public function testAccountOwnerBelongsToAdministratorAndGeneralGroups(bool $hasDefaultGroup): void
    {
        $identity = new IdentityIdentifier('69200000-0000-7000-8000-000000000098');
        $account = new AccountIdentifier('00000000-0000-7000-8000-000000000009');
        $principal = new Principal(new PrincipalIdentifier('69200000-0000-7000-8000-000000000099'), $identity, $account);
        $generalRole = new Role(new RoleIdentifier('69200000-0000-7000-8000-000000000096'), 'General', []);
        $administratorRole = new Role(new RoleIdentifier('69200000-0000-7000-8000-000000000095'), 'Administrator', []);
        $generalGroup = new PrincipalGroup(new PrincipalGroupIdentifier('69200000-0000-7000-8000-000000000097'), 'General', [$generalRole->roleIdentifier()], $account, true);
        $administratorGroup = new PrincipalGroup(new PrincipalGroupIdentifier('69200000-0000-7000-8000-000000000094'), 'SiteManagement Administrator', [], $account);

        $principalRepository = $this->createMock(PrincipalRepositoryInterface::class);
        $principalRepository->expects(self::once())->method('save')->with($principal);
        $principalFactory = $this->createMock(PrincipalFactoryInterface::class);
        $principalFactory->expects(self::once())->method('create')->with($identity, $account)->willReturn($principal);
        $principalGroupRepository = $this->createMock(PrincipalGroupRepositoryInterface::class);
        $principalGroupRepository->expects(self::once())->method('findDefaultByAccountIdentifier')->with($account)->willReturn($hasDefaultGroup ? $generalGroup : null);
        $principalGroupRepository->expects(self::once())->method('findByAccountIdAndName')->with($account, 'SiteManagement Administrator')->willReturn(null);
        $principalGroupRepository->expects(self::exactly(2))->method('save')->willReturnCallback(static function (PrincipalGroup $group) use ($principal): void {
            self::assertTrue($group->hasMember($principal->principalIdentifier()));
        });
        $principalGroupFactory = $this->createMock(PrincipalGroupFactoryInterface::class);
        $principalGroupFactory->expects(self::exactly($hasDefaultGroup ? 1 : 2))->method('create')->willReturnMap([
            ['General', [$generalRole->roleIdentifier()], $account, true, $generalGroup],
            ['SiteManagement Administrator', [], $account, false, $administratorGroup],
        ]);
        $roleRepository = $this->createMock(RoleRepositoryInterface::class);
        $roleRepository->expects(self::exactly($hasDefaultGroup ? 1 : 2))->method('findSystemByName')->willReturnMap([
            ['General', $generalRole], ['Administrator', $administratorRole],
        ]);

        $accountPrincipal = new AccountPrincipal(new AccountPrincipalIdentifier('69200000-0000-7000-8000-000000000093'), $identity, $account);
        $ownerRole = new AccountRole(new AccountRoleIdentifier('69200000-0000-7000-8000-000000000092'), AccountRole::OWNER, [], null);
        $ownerGroup = new AccountPrincipalGroup(new AccountPrincipalGroupIdentifier('69200000-0000-7000-8000-000000000091'), $account, 'Owners', false, new DateTimeImmutable());
        $ownerGroup->addMember($accountPrincipal->principalIdentifier());
        $accountPrincipalRepository = $this->createMock(AccountPrincipalRepositoryInterface::class);
        $accountPrincipalRepository->expects(self::once())->method('findByIdentityIdentifierAndAccountIdentifier')->with($identity, $account)->willReturn($accountPrincipal);
        $accountRoleRepository = $this->createMock(AccountRoleRepositoryInterface::class);
        $accountRoleRepository->expects(self::once())->method('findSystemByName')->with(AccountRole::OWNER)->willReturn($ownerRole);
        $accountPrincipalGroupRepository = $this->createMock(AccountPrincipalGroupRepositoryInterface::class);
        $accountPrincipalGroupRepository->expects(self::once())->method('findByAccountIdAndRole')->with($account, $ownerRole->roleIdentifier())->willReturn($ownerGroup);
        $output = new ProvisionPrincipalOutput();
        (new ProvisionPrincipal($principalRepository, $principalFactory, $principalGroupRepository, $principalGroupFactory, $roleRepository, $accountPrincipalRepository, $accountPrincipalGroupRepository, $accountRoleRepository))
            ->process(new ProvisionPrincipalInput($identity, $account), $output);

        self::assertSame($principal, $output->principal());
        self::assertTrue($generalGroup->hasMember($principal->principalIdentifier()));
        self::assertTrue($administratorGroup->hasMember($principal->principalIdentifier()));
        self::assertSame([$generalRole->roleIdentifier()], $generalGroup->roles());
        self::assertSame([$administratorRole->roleIdentifier()], $administratorGroup->roles());
    }

    /** @return array<string, array{bool}> */
    public static function defaultGroupStates(): array
    {
        return ['new default group' => [false], 'existing default group' => [true]];
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
        (new ProvisionPrincipal($repository, $factory, $groups, $this->createMock(PrincipalGroupFactoryInterface::class), $this->createMock(RoleRepositoryInterface::class), $this->createMock(AccountPrincipalRepositoryInterface::class), $this->createMock(AccountPrincipalGroupRepositoryInterface::class), $this->createMock(AccountRoleRepositoryInterface::class)))->process(new ProvisionPrincipalInput($identity, new AccountIdentifier('00000000-0000-7000-8000-000000000009')), $output);
        self::assertSame($principal, $output->principal());
    }
}
