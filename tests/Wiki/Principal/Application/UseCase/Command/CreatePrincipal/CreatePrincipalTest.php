<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Application\UseCase\Command\CreatePrincipal;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Source\Account\Principal\Domain\Entity\Principal as AccountPrincipal;
use Source\Account\Principal\Domain\Repository\PrincipalRepositoryInterface as AccountPrincipalRepositoryInterface;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier as AccountPrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Principal\Application\Exception\SystemRoleNotFoundException;
use Source\Wiki\Principal\Application\UseCase\Command\CreatePrincipal\CreatePrincipalInput;
use Source\Wiki\Principal\Application\UseCase\Command\CreatePrincipal\CreatePrincipalInterface;
use Source\Wiki\Principal\Application\UseCase\Command\CreatePrincipal\CreatePrincipalOutput;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Entity\PrincipalGroup;
use Source\Wiki\Principal\Domain\Entity\Role;
use Source\Wiki\Principal\Domain\Exception\PrincipalAlreadyExistsException;
use Source\Wiki\Principal\Domain\Factory\PrincipalFactoryInterface;
use Source\Wiki\Principal\Domain\Factory\PrincipalGroupFactoryInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\Wiki\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\RoleIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class CreatePrincipalTest extends TestCase
{
    /**
     * 正常系: Default PrincipalGroup が存在しない場合、Collaboratorロールを持つDefaultグループを作成してPrincipalを追加すること.
     *
     * @throws BindingResolutionException
     * @throws PrincipalAlreadyExistsException
     */
    public function testProcessCreatesDefaultPrincipalGroupWithCollaboratorRole(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principalGroupIdentifier = new PrincipalGroupIdentifier(StrTestHelper::generateUuid());
        $roleIdentifier = new RoleIdentifier(StrTestHelper::generateUuid());

        $principal = new Principal($principalIdentifier, $identityIdentifier, $accountIdentifier);
        $defaultPrincipalGroup = new PrincipalGroup(
            $principalGroupIdentifier,
            $accountIdentifier,
            'Default',
            true,
            new DateTimeImmutable(),
        );
        $collaboratorRole = new Role(
            $roleIdentifier,
            'Collaborator',
            [],
            null,
            new DateTimeImmutable(),
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findByIdentityIdentifierAndAccountIdentifier')
            ->with($identityIdentifier, $accountIdentifier)
            ->once()
            ->andReturnNull();
        $principalRepository->shouldReceive('save')
            ->with($principal)
            ->once();

        $principalFactory = Mockery::mock(PrincipalFactoryInterface::class);
        $principalFactory->shouldReceive('create')
            ->with($identityIdentifier, $accountIdentifier)
            ->once()
            ->andReturn($principal);

        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $principalGroupRepository->shouldReceive('findDefaultByAccountId')
            ->with($accountIdentifier)
            ->once()
            ->andReturnNull();
        $principalGroupRepository->shouldReceive('save')
            ->with($defaultPrincipalGroup)
            ->twice();

        $principalGroupFactory = Mockery::mock(PrincipalGroupFactoryInterface::class);
        $principalGroupFactory->shouldReceive('create')
            ->with($accountIdentifier, 'Default', true)
            ->once()
            ->andReturn($defaultPrincipalGroup);

        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);
        $roleRepository->shouldReceive('findSystemByName')
            ->with('Collaborator')
            ->once()
            ->andReturn($collaboratorRole);

        $this->app()->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app()->instance(PrincipalFactoryInterface::class, $principalFactory);
        $this->app()->instance(PrincipalGroupRepositoryInterface::class, $principalGroupRepository);
        $this->app()->instance(PrincipalGroupFactoryInterface::class, $principalGroupFactory);
        $this->app()->instance(RoleRepositoryInterface::class, $roleRepository);
        $accountPrincipalRepository = Mockery::mock(AccountPrincipalRepositoryInterface::class);
        $accountPrincipalRepository->shouldReceive('findOwnersByAccountId')->once()->with($accountIdentifier)->andReturn([]);
        $this->app()->instance(AccountPrincipalRepositoryInterface::class, $accountPrincipalRepository);

        $output = new CreatePrincipalOutput();
        $this->app()->make(CreatePrincipalInterface::class)->process(
            new CreatePrincipalInput($identityIdentifier, $accountIdentifier),
            $output,
        );

        $this->assertSame((string) $principalIdentifier, $output->toArray()['principalIdentifier']);
        $this->assertTrue($defaultPrincipalGroup->hasRole($roleIdentifier));
        $this->assertTrue($defaultPrincipalGroup->hasMember($principalIdentifier));
    }

    #[DataProvider('defaultGroupStates')]
    public function testProcessSeparatesAdministratorAndDefaultRolesForAccountOwner(bool $hasDefaultGroup, bool $hasInitialGroups): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $accountPrincipalIdentifier = new AccountPrincipalIdentifier(StrTestHelper::generateUuid());
        $wikiAdministratorRoleIdentifier = new RoleIdentifier(StrTestHelper::generateUuid());
        $collaboratorRoleIdentifier = new RoleIdentifier(StrTestHelper::generateUuid());

        $principal = new Principal($principalIdentifier, $identityIdentifier, $accountIdentifier);
        $accountPrincipal = new AccountPrincipal($accountPrincipalIdentifier, $identityIdentifier, $accountIdentifier);
        $wikiAdministratorPrincipalGroup = new PrincipalGroup(
            new PrincipalGroupIdentifier(StrTestHelper::generateUuid()),
            $accountIdentifier,
            'Wiki Administrator',
            false,
            new DateTimeImmutable(),
        );
        $wikiAdministratorRole = new Role(
            $wikiAdministratorRoleIdentifier,
            'Administrator',
            [],
            null,
            new DateTimeImmutable(),
        );
        $collaboratorRole = new Role(
            $collaboratorRoleIdentifier,
            'Collaborator',
            [],
            null,
            new DateTimeImmutable(),
        );

        $defaultGroup = new PrincipalGroup(
            new PrincipalGroupIdentifier(StrTestHelper::generateUuid()),
            $accountIdentifier,
            'Default',
            true,
            new DateTimeImmutable(),
        );
        if ($hasDefaultGroup) {
            $defaultGroup->addRole($collaboratorRole);
        }

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findByIdentityIdentifierAndAccountIdentifier')->once()->with($identityIdentifier, $accountIdentifier)->andReturnNull();
        $principalRepository->shouldReceive('save')->once()->with($principal);

        $principalFactory = Mockery::mock(PrincipalFactoryInterface::class);
        $principalFactory->shouldReceive('create')->once()->with($identityIdentifier, $accountIdentifier)->andReturn($principal);

        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $principalGroupRepository->shouldReceive('findByAccountIdAndName')
            ->once()
            ->with($accountIdentifier, 'Wiki Administrator')
            ->andReturnNull();
        $principalGroupRepository->shouldReceive('save')->twice()->with($wikiAdministratorPrincipalGroup);
        $actorGroup = new PrincipalGroup(new PrincipalGroupIdentifier(StrTestHelper::generateUuid()), $accountIdentifier, 'Talent Actor', false, new DateTimeImmutable());
        $affiliationGroup = new PrincipalGroup(new PrincipalGroupIdentifier(StrTestHelper::generateUuid()), $accountIdentifier, 'Affiliation - Agency', false, new DateTimeImmutable());
        $principalGroupRepository->shouldReceive('findInitialOwnerGroupsByAccountId')->once()->with($accountIdentifier)->andReturn($hasInitialGroups ? [$actorGroup, $affiliationGroup] : []);
        if ($hasInitialGroups) {
            $principalGroupRepository->shouldReceive('save')->once()->with($actorGroup);
            $principalGroupRepository->shouldReceive('save')->once()->with($affiliationGroup);
        }

        $principalGroupRepository->shouldReceive('findDefaultByAccountId')->once()->with($accountIdentifier)
            ->andReturn($hasDefaultGroup ? $defaultGroup : null);
        $principalGroupRepository->shouldReceive('save')->times($hasDefaultGroup ? 1 : 2)->with($defaultGroup);

        $principalGroupFactory = Mockery::mock(PrincipalGroupFactoryInterface::class);
        $principalGroupFactory->shouldReceive('create')
            ->once()
            ->with($accountIdentifier, 'Wiki Administrator', false)
            ->andReturn($wikiAdministratorPrincipalGroup);
        if (! $hasDefaultGroup) {
            $principalGroupFactory->shouldReceive('create')->once()->with($accountIdentifier, 'Default', true)->andReturn($defaultGroup);
        }

        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);
        $roleRepository->shouldReceive('findSystemByName')->once()->with('Administrator')->andReturn($wikiAdministratorRole);
        if (! $hasDefaultGroup) {
            $roleRepository->shouldReceive('findSystemByName')->once()->with('Collaborator')->andReturn($collaboratorRole);
        }

        $accountPrincipalRepository = Mockery::mock(AccountPrincipalRepositoryInterface::class);
        $accountPrincipalRepository->shouldReceive('findOwnersByAccountId')
            ->once()
            ->with($accountIdentifier)
            ->andReturn([$accountPrincipal]);

        $this->app()->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app()->instance(PrincipalFactoryInterface::class, $principalFactory);
        $this->app()->instance(PrincipalGroupRepositoryInterface::class, $principalGroupRepository);
        $this->app()->instance(PrincipalGroupFactoryInterface::class, $principalGroupFactory);
        $this->app()->instance(RoleRepositoryInterface::class, $roleRepository);
        $this->app()->instance(AccountPrincipalRepositoryInterface::class, $accountPrincipalRepository);

        $output = new CreatePrincipalOutput();
        $this->app()->make(CreatePrincipalInterface::class)->process(
            new CreatePrincipalInput($identityIdentifier, $accountIdentifier),
            $output,
        );

        $this->assertSame((string) $principalIdentifier, $output->toArray()['principalIdentifier']);
        $this->assertTrue($wikiAdministratorPrincipalGroup->hasRole($wikiAdministratorRoleIdentifier));
        $this->assertFalse($wikiAdministratorPrincipalGroup->hasRole($collaboratorRoleIdentifier));
        $this->assertNotEmpty((string) $wikiAdministratorPrincipalGroup->description());
        if (! $hasDefaultGroup) {
            $this->assertNotEmpty((string) $defaultGroup->description());
        }
        $this->assertTrue($defaultGroup->hasRole($collaboratorRoleIdentifier));
        $this->assertTrue($defaultGroup->hasMember($principalIdentifier));
        $this->assertTrue($wikiAdministratorPrincipalGroup->hasMember($principalIdentifier));
        $this->assertSame($hasInitialGroups, $actorGroup->hasMember($principalIdentifier));
        $this->assertSame($hasInitialGroups, $affiliationGroup->hasMember($principalIdentifier));
    }

    /** @return array<string, array{bool, bool}> */
    public static function defaultGroupStates(): array
    {
        return ['new default group' => [false, false], 'existing default group' => [true, false], 'late Owner with new default' => [false, true], 'late Owner with existing default' => [true, true]];
    }

    public function testProcessThrowsSystemRoleNotFoundWhenWikiAdministratorRoleDoesNotExist(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $accountPrincipalIdentifier = new AccountPrincipalIdentifier(StrTestHelper::generateUuid());

        $principal = new Principal($principalIdentifier, $identityIdentifier, $accountIdentifier);
        $accountPrincipal = new AccountPrincipal($accountPrincipalIdentifier, $identityIdentifier, $accountIdentifier);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findByIdentityIdentifierAndAccountIdentifier')->once()->with($identityIdentifier, $accountIdentifier)->andReturnNull();
        $principalRepository->shouldReceive('save')->once()->with($principal);

        $principalFactory = Mockery::mock(PrincipalFactoryInterface::class);
        $principalFactory->shouldReceive('create')->once()->with($identityIdentifier, $accountIdentifier)->andReturn($principal);

        $principalGroup = new PrincipalGroup(
            new PrincipalGroupIdentifier(StrTestHelper::generateUuid()),
            $accountIdentifier,
            'Wiki Administrator',
            false,
            new DateTimeImmutable(),
        );
        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $principalGroupRepository->shouldReceive('findByAccountIdAndName')->once()->andReturnNull();
        $principalGroupRepository->shouldNotReceive('save');

        $principalGroupFactory = Mockery::mock(PrincipalGroupFactoryInterface::class);
        $principalGroupFactory->shouldReceive('create')->once()->andReturn($principalGroup);

        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);
        $roleRepository->shouldReceive('findSystemByName')->once()->with('Administrator')->andReturnNull();

        $accountPrincipalRepository = Mockery::mock(AccountPrincipalRepositoryInterface::class);
        $accountPrincipalRepository->shouldReceive('findOwnersByAccountId')->once()->with($accountIdentifier)->andReturn([$accountPrincipal]);

        $this->app()->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app()->instance(PrincipalFactoryInterface::class, $principalFactory);
        $this->app()->instance(PrincipalGroupRepositoryInterface::class, $principalGroupRepository);
        $this->app()->instance(PrincipalGroupFactoryInterface::class, $principalGroupFactory);
        $this->app()->instance(RoleRepositoryInterface::class, $roleRepository);
        $this->app()->instance(AccountPrincipalRepositoryInterface::class, $accountPrincipalRepository);

        $this->expectException(SystemRoleNotFoundException::class);
        $this->expectExceptionMessage('Administrator system role is not found.');

        $this->app()->make(CreatePrincipalInterface::class)->process(
            new CreatePrincipalInput($identityIdentifier, $accountIdentifier),
            new CreatePrincipalOutput(),
        );
    }

    public function testProcessThrowsSystemRoleNotFoundWhenCollaboratorRoleDoesNotExistForAccountOwner(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $accountPrincipalIdentifier = new AccountPrincipalIdentifier(StrTestHelper::generateUuid());
        $wikiAdministratorRoleIdentifier = new RoleIdentifier(StrTestHelper::generateUuid());

        $principal = new Principal($principalIdentifier, $identityIdentifier, $accountIdentifier);
        $accountPrincipal = new AccountPrincipal($accountPrincipalIdentifier, $identityIdentifier, $accountIdentifier);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findByIdentityIdentifierAndAccountIdentifier')->once()->with($identityIdentifier, $accountIdentifier)->andReturnNull();
        $principalRepository->shouldReceive('save')->once()->with($principal);

        $principalFactory = Mockery::mock(PrincipalFactoryInterface::class);
        $principalFactory->shouldReceive('create')->once()->with($identityIdentifier, $accountIdentifier)->andReturn($principal);

        $principalGroup = new PrincipalGroup(
            new PrincipalGroupIdentifier(StrTestHelper::generateUuid()),
            $accountIdentifier,
            'Wiki Administrator',
            false,
            new DateTimeImmutable(),
        );
        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $principalGroupRepository->shouldReceive('findByAccountIdAndName')->once()->andReturnNull();
        $principalGroupRepository->shouldReceive('save')->twice()->with($principalGroup);
        $principalGroupRepository->shouldReceive('findInitialOwnerGroupsByAccountId')->once()->with($accountIdentifier)->andReturn([]);
        $principalGroupRepository->shouldReceive('findDefaultByAccountId')->once()->with($accountIdentifier)->andReturnNull();

        $principalGroupFactory = Mockery::mock(PrincipalGroupFactoryInterface::class);
        $principalGroupFactory->shouldReceive('create')->once()->with($accountIdentifier, 'Wiki Administrator', false)->andReturn($principalGroup);
        $defaultGroup = new PrincipalGroup(new PrincipalGroupIdentifier(StrTestHelper::generateUuid()), $accountIdentifier, 'Default', true, new DateTimeImmutable());
        $principalGroupFactory->shouldReceive('create')->once()->with($accountIdentifier, 'Default', true)->andReturn($defaultGroup);

        $wikiAdministratorRole = new Role(
            $wikiAdministratorRoleIdentifier,
            'Administrator',
            [],
            null,
            new DateTimeImmutable(),
        );
        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);
        $roleRepository->shouldReceive('findSystemByName')->once()->with('Administrator')->andReturn($wikiAdministratorRole);
        $roleRepository->shouldReceive('findSystemByName')->once()->with('Collaborator')->andReturnNull();

        $accountPrincipalRepository = Mockery::mock(AccountPrincipalRepositoryInterface::class);
        $accountPrincipalRepository->shouldReceive('findOwnersByAccountId')->once()->with($accountIdentifier)->andReturn([$accountPrincipal]);

        $this->app()->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app()->instance(PrincipalFactoryInterface::class, $principalFactory);
        $this->app()->instance(PrincipalGroupRepositoryInterface::class, $principalGroupRepository);
        $this->app()->instance(PrincipalGroupFactoryInterface::class, $principalGroupFactory);
        $this->app()->instance(RoleRepositoryInterface::class, $roleRepository);
        $this->app()->instance(AccountPrincipalRepositoryInterface::class, $accountPrincipalRepository);

        $this->expectException(SystemRoleNotFoundException::class);
        $this->expectExceptionMessage('Collaborator system role is not found.');

        $this->app()->make(CreatePrincipalInterface::class)->process(
            new CreatePrincipalInput($identityIdentifier, $accountIdentifier),
            new CreatePrincipalOutput(),
        );
    }

    /**
     * 正常系: Default PrincipalGroup が既に存在する場合、ロール構成を変更せずPrincipalを追加すること.
     *
     * @throws BindingResolutionException
     * @throws PrincipalAlreadyExistsException
     */
    public function testProcessAddsPrincipalToExistingDefaultPrincipalGroup(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $principal = new Principal($principalIdentifier, $identityIdentifier, $accountIdentifier);
        $defaultPrincipalGroup = new PrincipalGroup(
            new PrincipalGroupIdentifier(StrTestHelper::generateUuid()),
            $accountIdentifier,
            'Default',
            true,
            new DateTimeImmutable(),
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findByIdentityIdentifierAndAccountIdentifier')
            ->with($identityIdentifier, $accountIdentifier)
            ->once()
            ->andReturnNull();
        $principalRepository->shouldReceive('save')
            ->with($principal)
            ->once();

        $principalFactory = Mockery::mock(PrincipalFactoryInterface::class);
        $principalFactory->shouldReceive('create')
            ->with($identityIdentifier, $accountIdentifier)
            ->once()
            ->andReturn($principal);

        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $principalGroupRepository->shouldReceive('findDefaultByAccountId')
            ->with($accountIdentifier)
            ->once()
            ->andReturn($defaultPrincipalGroup);
        $principalGroupRepository->shouldReceive('save')
            ->with($defaultPrincipalGroup)
            ->once();

        $principalGroupFactory = Mockery::mock(PrincipalGroupFactoryInterface::class);
        $principalGroupFactory->shouldNotReceive('create');

        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);
        $roleRepository->shouldNotReceive('findSystemByName');

        $this->app()->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app()->instance(PrincipalFactoryInterface::class, $principalFactory);
        $this->app()->instance(PrincipalGroupRepositoryInterface::class, $principalGroupRepository);
        $this->app()->instance(PrincipalGroupFactoryInterface::class, $principalGroupFactory);
        $this->app()->instance(RoleRepositoryInterface::class, $roleRepository);
        $accountPrincipalRepository = Mockery::mock(AccountPrincipalRepositoryInterface::class);
        $accountPrincipalRepository->shouldReceive('findOwnersByAccountId')->once()->with($accountIdentifier)->andReturn([]);
        $this->app()->instance(AccountPrincipalRepositoryInterface::class, $accountPrincipalRepository);

        $output = new CreatePrincipalOutput();
        $this->app()->make(CreatePrincipalInterface::class)->process(
            new CreatePrincipalInput($identityIdentifier, $accountIdentifier),
            $output,
        );

        $this->assertSame((string) $principalIdentifier, $output->toArray()['principalIdentifier']);
        $this->assertTrue($defaultPrincipalGroup->hasMember($principalIdentifier));
        $this->assertEmpty($defaultPrincipalGroup->roles());
    }

    /**
     * 異常系: すでに生成済みのPrincipalを作成しようとした場合、例外がスローされること.
     *
     * @throws BindingResolutionException
     */
    public function testProcessThrowsExceptionWhenPrincipalAlreadyExists(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findByIdentityIdentifierAndAccountIdentifier')
            ->with($identityIdentifier, $accountIdentifier)
            ->once()
            ->andReturn(new Principal(
                new PrincipalIdentifier(StrTestHelper::generateUuid()),
                $identityIdentifier,
                $accountIdentifier,
            ));
        $principalRepository->shouldNotReceive('save');

        $principalFactory = Mockery::mock(PrincipalFactoryInterface::class);
        $principalFactory->shouldNotReceive('create');

        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $principalGroupRepository->shouldNotReceive('findDefaultByAccountId');
        $principalGroupRepository->shouldNotReceive('save');

        $this->app()->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app()->instance(PrincipalFactoryInterface::class, $principalFactory);
        $this->app()->instance(PrincipalGroupRepositoryInterface::class, $principalGroupRepository);
        $this->app()->instance(PrincipalGroupFactoryInterface::class, Mockery::mock(PrincipalGroupFactoryInterface::class));
        $this->app()->instance(RoleRepositoryInterface::class, Mockery::mock(RoleRepositoryInterface::class));
        $this->app()->instance(AccountPrincipalRepositoryInterface::class, Mockery::mock(AccountPrincipalRepositoryInterface::class));

        $this->expectException(PrincipalAlreadyExistsException::class);

        $this->app()->make(CreatePrincipalInterface::class)->process(
            new CreatePrincipalInput($identityIdentifier, $accountIdentifier),
            new CreatePrincipalOutput(),
        );
    }
}
