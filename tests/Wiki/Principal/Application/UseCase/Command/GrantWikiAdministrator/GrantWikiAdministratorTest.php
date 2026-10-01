<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Application\UseCase\Command\GrantWikiAdministrator;

use Mockery;
use Mockery\MockInterface;
use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Principal\Application\Exception\PrincipalGroupNotFoundException as AccountPrincipalGroupNotFoundException;
use Source\Account\Principal\Application\Exception\PrincipalNotFoundException as AccountPrincipalNotFoundException;
use Source\Account\Principal\Domain\Entity\PrincipalGroup as AccountPrincipalGroup;
use Source\Account\Principal\Domain\Exception\SystemRoleNotFoundException as AccountSystemRoleNotFoundException;
use Source\Account\Principal\Domain\Repository\PrincipalGroupRepositoryInterface as AccountPrincipalGroupRepositoryInterface;
use Source\Account\Principal\Domain\Repository\PrincipalRepositoryInterface as AccountPrincipalRepositoryInterface;
use Source\Account\Principal\Domain\Repository\RoleRepositoryInterface as AccountRoleRepositoryInterface;
use Source\Identity\Domain\Exception\IdentityNotFoundException;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;
use Source\Wiki\Principal\Application\Exception\OperationsPermissionRequiredException;
use Source\Wiki\Principal\Application\Exception\PrincipalNotFoundException;
use Source\Wiki\Principal\Application\Exception\SystemRoleNotFoundException;
use Source\Wiki\Principal\Application\UseCase\Command\GrantWikiAdministrator\GrantWikiAdministrator;
use Source\Wiki\Principal\Application\UseCase\Command\GrantWikiAdministrator\GrantWikiAdministratorInput;
use Source\Wiki\Principal\Application\UseCase\Command\GrantWikiAdministrator\GrantWikiAdministratorOutput;
use Source\Wiki\Principal\Domain\Factory\PrincipalGroupFactoryInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\RoleRepositoryInterface;
use Tests\Helper\WikiAdministratorTestData;
use Tests\TestCase;

class GrantWikiAdministratorTest extends TestCase
{
    public function testCreatesAccountScopedAdministratorGroupAndGrantsRole(): void
    {
        $dependencies = GrantDependencies::create();
        $data = WikiAdministratorTestData::create();
        $this->expectOperationsMembership($dependencies, $data);
        $this->expectExistingWikiPrincipal($dependencies, $data);
        $dependencies->principalGroupRepository->shouldReceive('findByAccountIdAndName')->once()
            ->with($data->account->accountIdentifier(), 'Operations Wiki Administrators')->andReturnNull();
        $dependencies->principalGroupFactory->shouldReceive('create')->once()
            ->with($data->account->accountIdentifier(), 'Operations Wiki Administrators', false)
            ->andReturn($data->administratorGroup);
        $dependencies->principalGroupRepository->shouldReceive('save')->once()->with($data->administratorGroup);

        $this->subject($dependencies)->process(
            new GrantWikiAdministratorInput($data->email),
            new GrantWikiAdministratorOutput(),
        );

        $this->assertTrue($data->administratorGroup->hasRole($data->administratorRole->roleIdentifier()));
        $this->assertTrue($data->administratorGroup->hasMember($data->wikiPrincipal->principalIdentifier()));
        $this->assertSame($data->account->accountIdentifier(), $data->administratorGroup->accountIdentifier());
    }

    public function testUsesExistingGroupWithoutDuplicatingRoleOrMember(): void
    {
        $dependencies = GrantDependencies::create();
        $data = WikiAdministratorTestData::create();
        $data->administratorGroup->addRole($data->administratorRole);
        $data->administratorGroup->addMember($data->wikiPrincipal->principalIdentifier());
        $this->expectOperationsMembership($dependencies, $data);
        $this->expectExistingWikiPrincipal($dependencies, $data);
        $dependencies->principalGroupRepository->shouldReceive('findByAccountIdAndName')->once()
            ->andReturn($data->administratorGroup);
        $dependencies->principalGroupFactory->shouldNotReceive('create');
        $dependencies->principalGroupRepository->shouldReceive('save')->once()->with($data->administratorGroup);

        $this->subject($dependencies)->process(
            new GrantWikiAdministratorInput($data->email),
            new GrantWikiAdministratorOutput(),
        );

        $this->assertCount(1, $data->administratorGroup->roles());
        $this->assertCount(1, $data->administratorGroup->members());
    }

    public function testThrowsWhenWikiPrincipalDoesNotExist(): void
    {
        $dependencies = GrantDependencies::create();
        $data = WikiAdministratorTestData::create();
        $this->expectOperationsMembership($dependencies, $data);
        $dependencies->roleRepository->shouldReceive('findSystemByName')->once()
            ->with('ADMINISTRATOR')->andReturn($data->administratorRole);
        $dependencies->principalRepository->shouldReceive('findByIdentityIdentifierAndAccountIdentifier')->once()
            ->with($data->identity->identityIdentifier(), $data->account->accountIdentifier())
            ->andReturnNull();
        $dependencies->principalRepository->shouldNotReceive('save');
        $dependencies->principalGroupFactory->shouldNotReceive('create');
        $dependencies->principalGroupRepository->shouldNotReceive('save');

        $this->expectException(PrincipalNotFoundException::class);
        $this->expectExceptionMessage('Accountと同じメールアドレスのIdentityに紐づくWiki Principalが見つかりません。');
        $this->subject($dependencies)->process(
            new GrantWikiAdministratorInput($data->email),
            new GrantWikiAdministratorOutput(),
        );
    }

    public function testRejectsPrincipalThatOnlyBelongsToAccountWithoutOperationsMembership(): void
    {
        $dependencies = GrantDependencies::create();
        $data = WikiAdministratorTestData::create();
        $operationsGroup = new AccountPrincipalGroup(
            $data->operationsGroup->principalGroupIdentifier(),
            $data->account->accountIdentifier(),
            'Operations',
            false,
            $data->operationsGroup->createdAt(),
            [$data->operationsRole->roleIdentifier()],
        );
        $dependencies->identityRepository->shouldReceive('findByEmail')->once()->andReturn($data->identity);
        $dependencies->accountRepository->shouldReceive('findByEmail')->once()->andReturn($data->account);
        $dependencies->accountPrincipalRepository->shouldReceive('findByIdentityIdentifierAndAccountIdentifier')->once()
            ->andReturn($data->accountPrincipal);
        $dependencies->accountRoleRepository->shouldReceive('findSystemByName')->once()->andReturn($data->operationsRole);
        $dependencies->accountPrincipalGroupRepository->shouldReceive('findByAccountIdAndRole')->once()
            ->andReturn($operationsGroup);
        $dependencies->principalGroupRepository->shouldNotReceive('save');

        $this->expectException(OperationsPermissionRequiredException::class);
        $this->subject($dependencies)->process(
            new GrantWikiAdministratorInput($data->email),
            new GrantWikiAdministratorOutput(),
        );
    }

    public function testThrowsWhenIdentityDoesNotExist(): void
    {
        $dependencies = GrantDependencies::create();
        $data = WikiAdministratorTestData::create();
        $dependencies->identityRepository->shouldReceive('findByEmail')->once()->with($data->email)->andReturnNull();

        $this->expectException(IdentityNotFoundException::class);
        $this->subject($dependencies)->process(new GrantWikiAdministratorInput($data->email), new GrantWikiAdministratorOutput());
    }

    public function testThrowsWhenAccountDoesNotExist(): void
    {
        $dependencies = GrantDependencies::create();
        $data = WikiAdministratorTestData::create();
        $dependencies->identityRepository->shouldReceive('findByEmail')->once()->andReturn($data->identity);
        $dependencies->accountRepository->shouldReceive('findByEmail')->once()->andReturnNull();

        $this->expectException(AccountNotFoundException::class);
        $this->subject($dependencies)->process(new GrantWikiAdministratorInput($data->email), new GrantWikiAdministratorOutput());
    }

    public function testThrowsWhenAccountPrincipalDoesNotExist(): void
    {
        $dependencies = GrantDependencies::create();
        $data = WikiAdministratorTestData::create();
        $dependencies->identityRepository->shouldReceive('findByEmail')->once()->andReturn($data->identity);
        $dependencies->accountRepository->shouldReceive('findByEmail')->once()->andReturn($data->account);
        $dependencies->accountPrincipalRepository->shouldReceive('findByIdentityIdentifierAndAccountIdentifier')->once()
            ->andReturnNull();

        $this->expectException(AccountPrincipalNotFoundException::class);
        $this->subject($dependencies)->process(new GrantWikiAdministratorInput($data->email), new GrantWikiAdministratorOutput());
    }

    public function testThrowsWhenAdministratorSystemRoleDoesNotExist(): void
    {
        $dependencies = GrantDependencies::create();
        $data = WikiAdministratorTestData::create();
        $this->expectOperationsMembership($dependencies, $data);
        $dependencies->roleRepository->shouldReceive('findSystemByName')->once()->with('ADMINISTRATOR')->andReturnNull();

        $this->expectException(SystemRoleNotFoundException::class);
        $this->subject($dependencies)->process(new GrantWikiAdministratorInput($data->email), new GrantWikiAdministratorOutput());
    }

    public function testThrowsWhenOperationsSystemRoleDoesNotExist(): void
    {
        $dependencies = GrantDependencies::create();
        $data = WikiAdministratorTestData::create();
        $dependencies->identityRepository->shouldReceive('findByEmail')->once()->andReturn($data->identity);
        $dependencies->accountRepository->shouldReceive('findByEmail')->once()->andReturn($data->account);
        $dependencies->accountPrincipalRepository->shouldReceive('findByIdentityIdentifierAndAccountIdentifier')->once()
            ->andReturn($data->accountPrincipal);
        $dependencies->accountRoleRepository->shouldReceive('findSystemByName')->once()->andReturnNull();

        $this->expectException(AccountSystemRoleNotFoundException::class);
        $this->subject($dependencies)->process(new GrantWikiAdministratorInput($data->email), new GrantWikiAdministratorOutput());
    }

    public function testThrowsWhenOperationsGroupDoesNotExist(): void
    {
        $dependencies = GrantDependencies::create();
        $data = WikiAdministratorTestData::create();
        $dependencies->identityRepository->shouldReceive('findByEmail')->once()->andReturn($data->identity);
        $dependencies->accountRepository->shouldReceive('findByEmail')->once()->andReturn($data->account);
        $dependencies->accountPrincipalRepository->shouldReceive('findByIdentityIdentifierAndAccountIdentifier')->once()
            ->andReturn($data->accountPrincipal);
        $dependencies->accountRoleRepository->shouldReceive('findSystemByName')->once()->andReturn($data->operationsRole);
        $dependencies->accountPrincipalGroupRepository->shouldReceive('findByAccountIdAndRole')->once()->andReturnNull();

        $this->expectException(AccountPrincipalGroupNotFoundException::class);
        $this->subject($dependencies)->process(new GrantWikiAdministratorInput($data->email), new GrantWikiAdministratorOutput());
    }

    private function expectOperationsMembership(GrantDependencies $dependencies, WikiAdministratorTestData $data): void
    {
        $dependencies->identityRepository->shouldReceive('findByEmail')->once()->with($data->email)->andReturn($data->identity);
        $dependencies->accountRepository->shouldReceive('findByEmail')->once()->with($data->email)->andReturn($data->account);
        $dependencies->accountPrincipalRepository->shouldReceive('findByIdentityIdentifierAndAccountIdentifier')->once()
            ->with($data->identity->identityIdentifier(), $data->account->accountIdentifier())
            ->andReturn($data->accountPrincipal);
        $dependencies->accountRoleRepository->shouldReceive('findSystemByName')->once()
            ->with('Operations')->andReturn($data->operationsRole);
        $dependencies->accountPrincipalGroupRepository->shouldReceive('findByAccountIdAndRole')->once()
            ->with($data->account->accountIdentifier(), $data->operationsRole->roleIdentifier())
            ->andReturn($data->operationsGroup);
    }

    private function expectExistingWikiPrincipal(GrantDependencies $dependencies, WikiAdministratorTestData $data): void
    {
        $dependencies->roleRepository->shouldReceive('findSystemByName')->once()
            ->with('ADMINISTRATOR')->andReturn($data->administratorRole);
        $dependencies->principalRepository->shouldReceive('findByIdentityIdentifierAndAccountIdentifier')->once()
            ->with($data->identity->identityIdentifier(), $data->account->accountIdentifier())
            ->andReturn($data->wikiPrincipal);
    }

    private function subject(GrantDependencies $dependencies): GrantWikiAdministrator
    {
        return new GrantWikiAdministrator(
            $dependencies->identityRepository,
            $dependencies->accountRepository,
            $dependencies->accountPrincipalRepository,
            $dependencies->accountPrincipalGroupRepository,
            $dependencies->accountRoleRepository,
            $dependencies->principalRepository,
            $dependencies->principalGroupRepository,
            $dependencies->principalGroupFactory,
            $dependencies->roleRepository,
        );
    }
}

readonly class GrantDependencies
{
    public function __construct(
        public MockInterface&IdentityRepositoryInterface $identityRepository,
        public MockInterface&AccountRepositoryInterface $accountRepository,
        public MockInterface&AccountPrincipalRepositoryInterface $accountPrincipalRepository,
        public MockInterface&AccountPrincipalGroupRepositoryInterface $accountPrincipalGroupRepository,
        public MockInterface&AccountRoleRepositoryInterface $accountRoleRepository,
        public MockInterface&PrincipalRepositoryInterface $principalRepository,
        public MockInterface&PrincipalGroupRepositoryInterface $principalGroupRepository,
        public MockInterface&PrincipalGroupFactoryInterface $principalGroupFactory,
        public MockInterface&RoleRepositoryInterface $roleRepository,
    ) {
    }

    public static function create(): self
    {
        /** @var MockInterface&IdentityRepositoryInterface $identityRepository */
        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        /** @var MockInterface&AccountRepositoryInterface $accountRepository */
        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        /** @var MockInterface&AccountPrincipalRepositoryInterface $accountPrincipalRepository */
        $accountPrincipalRepository = Mockery::mock(AccountPrincipalRepositoryInterface::class);
        /** @var MockInterface&AccountPrincipalGroupRepositoryInterface $accountPrincipalGroupRepository */
        $accountPrincipalGroupRepository = Mockery::mock(AccountPrincipalGroupRepositoryInterface::class);
        /** @var MockInterface&AccountRoleRepositoryInterface $accountRoleRepository */
        $accountRoleRepository = Mockery::mock(AccountRoleRepositoryInterface::class);
        /** @var MockInterface&PrincipalRepositoryInterface $principalRepository */
        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        /** @var MockInterface&PrincipalGroupRepositoryInterface $principalGroupRepository */
        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        /** @var MockInterface&PrincipalGroupFactoryInterface $principalGroupFactory */
        $principalGroupFactory = Mockery::mock(PrincipalGroupFactoryInterface::class);
        /** @var MockInterface&RoleRepositoryInterface $roleRepository */
        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);

        return new self(
            $identityRepository,
            $accountRepository,
            $accountPrincipalRepository,
            $accountPrincipalGroupRepository,
            $accountRoleRepository,
            $principalRepository,
            $principalGroupRepository,
            $principalGroupFactory,
            $roleRepository,
        );
    }
}
