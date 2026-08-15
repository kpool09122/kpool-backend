<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Application\UseCase\Command\CreatePrincipal;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
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
     * 正常系: Default PrincipalGroup が存在しない場合、COLLABORATORロールを持つDefaultグループを作成してPrincipalを追加すること.
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

        $principal = new Principal($principalIdentifier, $identityIdentifier, null, [], []);
        $defaultPrincipalGroup = new PrincipalGroup(
            $principalGroupIdentifier,
            $accountIdentifier,
            'Default',
            true,
            new DateTimeImmutable(),
        );
        $collaboratorRole = new Role(
            $roleIdentifier,
            'COLLABORATOR',
            [],
            true,
            new DateTimeImmutable(),
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findByIdentityIdentifier')
            ->with($identityIdentifier)
            ->once()
            ->andReturnNull();
        $principalRepository->shouldReceive('save')
            ->with($principal)
            ->once();

        $principalFactory = Mockery::mock(PrincipalFactoryInterface::class);
        $principalFactory->shouldReceive('create')
            ->with($identityIdentifier)
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
        $roleRepository->shouldReceive('findByName')
            ->with('COLLABORATOR')
            ->once()
            ->andReturn($collaboratorRole);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(PrincipalFactoryInterface::class, $principalFactory);
        $this->app->instance(PrincipalGroupRepositoryInterface::class, $principalGroupRepository);
        $this->app->instance(PrincipalGroupFactoryInterface::class, $principalGroupFactory);
        $this->app->instance(RoleRepositoryInterface::class, $roleRepository);
        $accountPrincipalRepository = Mockery::mock(AccountPrincipalRepositoryInterface::class);
        $accountPrincipalRepository->shouldReceive('findByIdentityIdentifierAndAccountIdentifier')->once()->andReturnNull();
        $this->app->instance(AccountPrincipalRepositoryInterface::class, $accountPrincipalRepository);
        $this->app->instance(AccountPrincipalGroupRepositoryInterface::class, Mockery::mock(AccountPrincipalGroupRepositoryInterface::class));
        $this->app->instance(AccountRoleRepositoryInterface::class, Mockery::mock(AccountRoleRepositoryInterface::class));

        $output = new CreatePrincipalOutput();
        $this->app->make(CreatePrincipalInterface::class)->process(
            new CreatePrincipalInput($identityIdentifier, $accountIdentifier),
            $output,
        );

        $this->assertSame((string) $principalIdentifier, $output->toArray()['principalIdentifier']);
        $this->assertTrue($defaultPrincipalGroup->hasRole($roleIdentifier));
        $this->assertTrue($defaultPrincipalGroup->hasMember($principalIdentifier));
    }

    public function testProcessCreatesWikiAdministratorPrincipalGroupWithCollaboratorRoleForAccountOwner(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $accountPrincipalIdentifier = new AccountPrincipalIdentifier(StrTestHelper::generateUuid());
        $ownerRoleIdentifier = new AccountRoleIdentifier(StrTestHelper::generateUuid());
        $wikiAdministratorRoleIdentifier = new RoleIdentifier(StrTestHelper::generateUuid());
        $collaboratorRoleIdentifier = new RoleIdentifier(StrTestHelper::generateUuid());

        $principal = new Principal($principalIdentifier, $identityIdentifier, null, [], []);
        $accountPrincipal = new AccountPrincipal($accountPrincipalIdentifier, $identityIdentifier, $accountIdentifier);
        $ownerRole = new AccountRole($ownerRoleIdentifier, AccountRole::OWNER, [], true);
        $ownerGroup = new AccountPrincipalGroup(
            new AccountPrincipalGroupIdentifier(StrTestHelper::generateUuid()),
            $accountIdentifier,
            'Owner',
            false,
            new DateTimeImmutable(),
        );
        $ownerGroup->addMember($accountPrincipalIdentifier);
        $wikiAdministratorPrincipalGroup = new PrincipalGroup(
            new PrincipalGroupIdentifier(StrTestHelper::generateUuid()),
            $accountIdentifier,
            'Wiki Administrator',
            false,
            new DateTimeImmutable(),
        );
        $wikiAdministratorRole = new Role(
            $wikiAdministratorRoleIdentifier,
            'WIKI_ADMINISTRATOR',
            [],
            true,
            new DateTimeImmutable(),
        );
        $collaboratorRole = new Role(
            $collaboratorRoleIdentifier,
            'COLLABORATOR',
            [],
            true,
            new DateTimeImmutable(),
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findByIdentityIdentifier')->once()->with($identityIdentifier)->andReturnNull();
        $principalRepository->shouldReceive('save')->once()->with($principal);

        $principalFactory = Mockery::mock(PrincipalFactoryInterface::class);
        $principalFactory->shouldReceive('create')->once()->with($identityIdentifier)->andReturn($principal);

        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $principalGroupRepository->shouldReceive('findByAccountIdAndName')
            ->once()
            ->with($accountIdentifier, 'Wiki Administrator')
            ->andReturnNull();
        $principalGroupRepository->shouldReceive('save')->twice()->with($wikiAdministratorPrincipalGroup);

        $principalGroupFactory = Mockery::mock(PrincipalGroupFactoryInterface::class);
        $principalGroupFactory->shouldReceive('create')
            ->once()
            ->with($accountIdentifier, 'Wiki Administrator', false)
            ->andReturn($wikiAdministratorPrincipalGroup);

        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);
        $roleRepository->shouldReceive('findByName')->once()->with('WIKI_ADMINISTRATOR')->andReturn($wikiAdministratorRole);
        $roleRepository->shouldReceive('findByName')->once()->with('COLLABORATOR')->andReturn($collaboratorRole);

        $accountPrincipalRepository = Mockery::mock(AccountPrincipalRepositoryInterface::class);
        $accountPrincipalRepository->shouldReceive('findByIdentityIdentifierAndAccountIdentifier')
            ->once()
            ->with($identityIdentifier, $accountIdentifier)
            ->andReturn($accountPrincipal);

        $accountRoleRepository = Mockery::mock(AccountRoleRepositoryInterface::class);
        $accountRoleRepository->shouldReceive('findByName')->once()->with(AccountRole::OWNER)->andReturn($ownerRole);

        $accountPrincipalGroupRepository = Mockery::mock(AccountPrincipalGroupRepositoryInterface::class);
        $accountPrincipalGroupRepository->shouldReceive('findByAccountIdAndRole')
            ->once()
            ->with($accountIdentifier, $ownerRoleIdentifier)
            ->andReturn($ownerGroup);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(PrincipalFactoryInterface::class, $principalFactory);
        $this->app->instance(PrincipalGroupRepositoryInterface::class, $principalGroupRepository);
        $this->app->instance(PrincipalGroupFactoryInterface::class, $principalGroupFactory);
        $this->app->instance(RoleRepositoryInterface::class, $roleRepository);
        $this->app->instance(AccountPrincipalRepositoryInterface::class, $accountPrincipalRepository);
        $this->app->instance(AccountPrincipalGroupRepositoryInterface::class, $accountPrincipalGroupRepository);
        $this->app->instance(AccountRoleRepositoryInterface::class, $accountRoleRepository);

        $output = new CreatePrincipalOutput();
        $this->app->make(CreatePrincipalInterface::class)->process(
            new CreatePrincipalInput($identityIdentifier, $accountIdentifier),
            $output,
        );

        $this->assertSame((string) $principalIdentifier, $output->toArray()['principalIdentifier']);
        $this->assertTrue($wikiAdministratorPrincipalGroup->hasRole($wikiAdministratorRoleIdentifier));
        $this->assertTrue($wikiAdministratorPrincipalGroup->hasRole($collaboratorRoleIdentifier));
        $this->assertTrue($wikiAdministratorPrincipalGroup->hasMember($principalIdentifier));
    }

    public function testProcessThrowsSystemRoleNotFoundWhenWikiAdministratorRoleDoesNotExist(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $accountPrincipalIdentifier = new AccountPrincipalIdentifier(StrTestHelper::generateUuid());
        $ownerRoleIdentifier = new AccountRoleIdentifier(StrTestHelper::generateUuid());

        $principal = new Principal($principalIdentifier, $identityIdentifier, null, [], []);
        $accountPrincipal = new AccountPrincipal($accountPrincipalIdentifier, $identityIdentifier, $accountIdentifier);
        $ownerRole = new AccountRole($ownerRoleIdentifier, AccountRole::OWNER, [], true);
        $ownerGroup = new AccountPrincipalGroup(
            new AccountPrincipalGroupIdentifier(StrTestHelper::generateUuid()),
            $accountIdentifier,
            'Owner',
            false,
            new DateTimeImmutable(),
        );
        $ownerGroup->addMember($accountPrincipalIdentifier);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findByIdentityIdentifier')->once()->with($identityIdentifier)->andReturnNull();
        $principalRepository->shouldReceive('save')->once()->with($principal);

        $principalFactory = Mockery::mock(PrincipalFactoryInterface::class);
        $principalFactory->shouldReceive('create')->once()->with($identityIdentifier)->andReturn($principal);

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
        $roleRepository->shouldReceive('findByName')->once()->with('WIKI_ADMINISTRATOR')->andReturnNull();

        $accountPrincipalRepository = Mockery::mock(AccountPrincipalRepositoryInterface::class);
        $accountPrincipalRepository->shouldReceive('findByIdentityIdentifierAndAccountIdentifier')->once()->andReturn($accountPrincipal);

        $accountRoleRepository = Mockery::mock(AccountRoleRepositoryInterface::class);
        $accountRoleRepository->shouldReceive('findByName')->once()->with(AccountRole::OWNER)->andReturn($ownerRole);

        $accountPrincipalGroupRepository = Mockery::mock(AccountPrincipalGroupRepositoryInterface::class);
        $accountPrincipalGroupRepository->shouldReceive('findByAccountIdAndRole')->once()->andReturn($ownerGroup);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(PrincipalFactoryInterface::class, $principalFactory);
        $this->app->instance(PrincipalGroupRepositoryInterface::class, $principalGroupRepository);
        $this->app->instance(PrincipalGroupFactoryInterface::class, $principalGroupFactory);
        $this->app->instance(RoleRepositoryInterface::class, $roleRepository);
        $this->app->instance(AccountPrincipalRepositoryInterface::class, $accountPrincipalRepository);
        $this->app->instance(AccountPrincipalGroupRepositoryInterface::class, $accountPrincipalGroupRepository);
        $this->app->instance(AccountRoleRepositoryInterface::class, $accountRoleRepository);

        $this->expectException(SystemRoleNotFoundException::class);
        $this->expectExceptionMessage('WIKI_ADMINISTRATOR system role is not found.');

        $this->app->make(CreatePrincipalInterface::class)->process(
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
        $ownerRoleIdentifier = new AccountRoleIdentifier(StrTestHelper::generateUuid());
        $wikiAdministratorRoleIdentifier = new RoleIdentifier(StrTestHelper::generateUuid());

        $principal = new Principal($principalIdentifier, $identityIdentifier, null, [], []);
        $accountPrincipal = new AccountPrincipal($accountPrincipalIdentifier, $identityIdentifier, $accountIdentifier);
        $ownerRole = new AccountRole($ownerRoleIdentifier, AccountRole::OWNER, [], true);
        $ownerGroup = new AccountPrincipalGroup(
            new AccountPrincipalGroupIdentifier(StrTestHelper::generateUuid()),
            $accountIdentifier,
            'Owner',
            false,
            new DateTimeImmutable(),
        );
        $ownerGroup->addMember($accountPrincipalIdentifier);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findByIdentityIdentifier')->once()->with($identityIdentifier)->andReturnNull();
        $principalRepository->shouldReceive('save')->once()->with($principal);

        $principalFactory = Mockery::mock(PrincipalFactoryInterface::class);
        $principalFactory->shouldReceive('create')->once()->with($identityIdentifier)->andReturn($principal);

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

        $wikiAdministratorRole = new Role(
            $wikiAdministratorRoleIdentifier,
            'WIKI_ADMINISTRATOR',
            [],
            true,
            new DateTimeImmutable(),
        );
        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);
        $roleRepository->shouldReceive('findByName')->once()->with('WIKI_ADMINISTRATOR')->andReturn($wikiAdministratorRole);
        $roleRepository->shouldReceive('findByName')->once()->with('COLLABORATOR')->andReturnNull();

        $accountPrincipalRepository = Mockery::mock(AccountPrincipalRepositoryInterface::class);
        $accountPrincipalRepository->shouldReceive('findByIdentityIdentifierAndAccountIdentifier')->once()->andReturn($accountPrincipal);

        $accountRoleRepository = Mockery::mock(AccountRoleRepositoryInterface::class);
        $accountRoleRepository->shouldReceive('findByName')->once()->with(AccountRole::OWNER)->andReturn($ownerRole);

        $accountPrincipalGroupRepository = Mockery::mock(AccountPrincipalGroupRepositoryInterface::class);
        $accountPrincipalGroupRepository->shouldReceive('findByAccountIdAndRole')->once()->andReturn($ownerGroup);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(PrincipalFactoryInterface::class, $principalFactory);
        $this->app->instance(PrincipalGroupRepositoryInterface::class, $principalGroupRepository);
        $this->app->instance(PrincipalGroupFactoryInterface::class, $principalGroupFactory);
        $this->app->instance(RoleRepositoryInterface::class, $roleRepository);
        $this->app->instance(AccountPrincipalRepositoryInterface::class, $accountPrincipalRepository);
        $this->app->instance(AccountPrincipalGroupRepositoryInterface::class, $accountPrincipalGroupRepository);
        $this->app->instance(AccountRoleRepositoryInterface::class, $accountRoleRepository);

        $this->expectException(SystemRoleNotFoundException::class);
        $this->expectExceptionMessage('COLLABORATOR system role is not found.');

        $this->app->make(CreatePrincipalInterface::class)->process(
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

        $principal = new Principal($principalIdentifier, $identityIdentifier, null, [], []);
        $defaultPrincipalGroup = new PrincipalGroup(
            new PrincipalGroupIdentifier(StrTestHelper::generateUuid()),
            $accountIdentifier,
            'Default',
            true,
            new DateTimeImmutable(),
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findByIdentityIdentifier')
            ->with($identityIdentifier)
            ->once()
            ->andReturnNull();
        $principalRepository->shouldReceive('save')
            ->with($principal)
            ->once();

        $principalFactory = Mockery::mock(PrincipalFactoryInterface::class);
        $principalFactory->shouldReceive('create')
            ->with($identityIdentifier)
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
        $roleRepository->shouldNotReceive('findByName');

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(PrincipalFactoryInterface::class, $principalFactory);
        $this->app->instance(PrincipalGroupRepositoryInterface::class, $principalGroupRepository);
        $this->app->instance(PrincipalGroupFactoryInterface::class, $principalGroupFactory);
        $this->app->instance(RoleRepositoryInterface::class, $roleRepository);
        $accountPrincipalRepository = Mockery::mock(AccountPrincipalRepositoryInterface::class);
        $accountPrincipalRepository->shouldReceive('findByIdentityIdentifierAndAccountIdentifier')->once()->andReturnNull();
        $this->app->instance(AccountPrincipalRepositoryInterface::class, $accountPrincipalRepository);
        $this->app->instance(AccountPrincipalGroupRepositoryInterface::class, Mockery::mock(AccountPrincipalGroupRepositoryInterface::class));
        $this->app->instance(AccountRoleRepositoryInterface::class, Mockery::mock(AccountRoleRepositoryInterface::class));

        $output = new CreatePrincipalOutput();
        $this->app->make(CreatePrincipalInterface::class)->process(
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
        $principalRepository->shouldReceive('findByIdentityIdentifier')
            ->with($identityIdentifier)
            ->once()
            ->andReturn(new Principal(
                new PrincipalIdentifier(StrTestHelper::generateUuid()),
                $identityIdentifier,
                null,
                [],
                [],
            ));
        $principalRepository->shouldNotReceive('save');

        $principalFactory = Mockery::mock(PrincipalFactoryInterface::class);
        $principalFactory->shouldNotReceive('create');

        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $principalGroupRepository->shouldNotReceive('findDefaultByAccountId');
        $principalGroupRepository->shouldNotReceive('save');

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(PrincipalFactoryInterface::class, $principalFactory);
        $this->app->instance(PrincipalGroupRepositoryInterface::class, $principalGroupRepository);
        $this->app->instance(PrincipalGroupFactoryInterface::class, Mockery::mock(PrincipalGroupFactoryInterface::class));
        $this->app->instance(RoleRepositoryInterface::class, Mockery::mock(RoleRepositoryInterface::class));
        $this->app->instance(AccountPrincipalRepositoryInterface::class, Mockery::mock(AccountPrincipalRepositoryInterface::class));
        $this->app->instance(AccountPrincipalGroupRepositoryInterface::class, Mockery::mock(AccountPrincipalGroupRepositoryInterface::class));
        $this->app->instance(AccountRoleRepositoryInterface::class, Mockery::mock(AccountRoleRepositoryInterface::class));

        $this->expectException(PrincipalAlreadyExistsException::class);

        $this->app->make(CreatePrincipalInterface::class)->process(
            new CreatePrincipalInput($identityIdentifier, $accountIdentifier),
            new CreatePrincipalOutput(),
        );
    }
}
