<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Application\UseCase\Command\RevokeWikiAdministrator;

use Mockery;
use Mockery\MockInterface;
use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;
use Source\Wiki\Principal\Application\Exception\AdministratorRoleNotAttachedException;
use Source\Wiki\Principal\Application\Exception\PrincipalGroupNotFoundException;
use Source\Wiki\Principal\Application\Exception\SystemRoleNotFoundException;
use Source\Wiki\Principal\Application\UseCase\Command\RevokeWikiAdministrator\RevokeWikiAdministrator;
use Source\Wiki\Principal\Application\UseCase\Command\RevokeWikiAdministrator\RevokeWikiAdministratorInput;
use Source\Wiki\Principal\Application\UseCase\Command\RevokeWikiAdministrator\RevokeWikiAdministratorInterface;
use Source\Wiki\Principal\Application\UseCase\Command\RevokeWikiAdministrator\RevokeWikiAdministratorOutput;
use Source\Wiki\Principal\Domain\Entity\PrincipalGroup;
use Source\Wiki\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\Helper\WikiAdministratorTestData;
use Tests\TestCase;

class RevokeWikiAdministratorTest extends TestCase
{
    public function testDeletesTheWholeTargetAccountAdministratorGroupIncludingTheLastAdministrator(): void
    {
        $dependencies = RevokeDependencies::create();
        $data = WikiAdministratorTestData::create();
        $data->administratorGroup->addRole($data->administratorRole);
        $data->administratorGroup->addMember($data->wikiPrincipal->principalIdentifier());
        $this->expectTarget($dependencies, $data, $data->administratorGroup);
        $dependencies->principalGroupRepository->shouldReceive('delete')->once()->with($data->administratorGroup);
        $dependencies->principalRepository->shouldNotReceive('save');

        $this->subject($dependencies)->process(
            new RevokeWikiAdministratorInput($data->email),
            new RevokeWikiAdministratorOutput(),
        );

        $this->assertSame(1, $data->administratorGroup->memberCount());
    }

    public function testDoesNotCheckAccountOperationsMembershipBeforeRevocation(): void
    {
        $dependencies = RevokeDependencies::create();
        $data = WikiAdministratorTestData::create();
        $data->administratorGroup->addRole($data->administratorRole);
        $data->administratorGroup->addMember($data->wikiPrincipal->principalIdentifier());
        $this->expectTarget($dependencies, $data, $data->administratorGroup);
        $dependencies->principalGroupRepository->shouldReceive('delete')->once();

        $this->subject($dependencies)->process(
            new RevokeWikiAdministratorInput($data->email),
            new RevokeWikiAdministratorOutput(),
        );

    }

    public function testScopesGroupLookupToTheTargetAccount(): void
    {
        $dependencies = RevokeDependencies::create();
        $data = WikiAdministratorTestData::create();
        $data->administratorGroup->addRole($data->administratorRole);
        $data->administratorGroup->addMember($data->wikiPrincipal->principalIdentifier());
        $dependencies->accountRepository->shouldReceive('findByEmail')->once()->andReturn($data->account);
        $dependencies->roleRepository->shouldReceive('findSystemByName')->once()->andReturn($data->administratorRole);
        $dependencies->principalGroupRepository->shouldReceive('findByAccountIdAndName')->once()
            ->with($data->account->accountIdentifier(), 'Operations Wiki Administrators')
            ->andReturn($data->administratorGroup);
        $dependencies->principalGroupRepository->shouldReceive('delete')->once()->with($data->administratorGroup);

        $this->subject($dependencies)->process(
            new RevokeWikiAdministratorInput($data->email),
            new RevokeWikiAdministratorOutput(),
        );
    }

    public function testThrowsWhenAccountDoesNotExist(): void
    {
        $dependencies = RevokeDependencies::create();
        $data = WikiAdministratorTestData::create();
        $dependencies->accountRepository->shouldReceive('findByEmail')->once()->andReturnNull();

        $this->expectException(AccountNotFoundException::class);
        $this->subject($dependencies)->process(new RevokeWikiAdministratorInput($data->email), new RevokeWikiAdministratorOutput());
    }

    public function testThrowsWhenAdministratorSystemRoleDoesNotExist(): void
    {
        $dependencies = RevokeDependencies::create();
        $data = WikiAdministratorTestData::create();
        $dependencies->accountRepository->shouldReceive('findByEmail')->once()->andReturn($data->account);
        $dependencies->roleRepository->shouldReceive('findSystemByName')->once()->andReturnNull();

        $this->expectException(SystemRoleNotFoundException::class);
        $this->subject($dependencies)->process(new RevokeWikiAdministratorInput($data->email), new RevokeWikiAdministratorOutput());
    }

    public function testThrowsWhenAdministratorGroupDoesNotExistIncludingRepeatedRevocation(): void
    {
        $dependencies = RevokeDependencies::create();
        $data = WikiAdministratorTestData::create();
        $this->expectTarget($dependencies, $data, null);
        $dependencies->principalGroupRepository->shouldNotReceive('delete');

        $this->expectException(PrincipalGroupNotFoundException::class);
        $this->subject($dependencies)->process(new RevokeWikiAdministratorInput($data->email), new RevokeWikiAdministratorOutput());
    }

    public function testThrowsWhenAdministratorRoleIsNotAttached(): void
    {
        $dependencies = RevokeDependencies::create();
        $data = WikiAdministratorTestData::create();
        $data->administratorGroup->addMember($data->wikiPrincipal->principalIdentifier());
        $this->expectTarget($dependencies, $data, $data->administratorGroup);
        $dependencies->principalGroupRepository->shouldNotReceive('delete');

        $this->expectException(AdministratorRoleNotAttachedException::class);
        $this->subject($dependencies)->process(new RevokeWikiAdministratorInput($data->email), new RevokeWikiAdministratorOutput());
    }

    public function testDeletesGroupAfterOriginalPrincipalWasRemoved(): void
    {
        $dependencies = RevokeDependencies::create();
        $data = WikiAdministratorTestData::create();
        $data->administratorGroup->addRole($data->administratorRole);
        $data->administratorGroup->addMember(new PrincipalIdentifier(StrTestHelper::generateUuid()));
        $this->assertFalse($data->administratorGroup->hasMember($data->wikiPrincipal->principalIdentifier()));
        $this->expectTarget($dependencies, $data, $data->administratorGroup);
        $dependencies->principalGroupRepository->shouldReceive('delete')->once()->with($data->administratorGroup);

        $this->subject($dependencies)->process(new RevokeWikiAdministratorInput($data->email), new RevokeWikiAdministratorOutput());
    }

    private function expectTarget(
        RevokeDependencies $dependencies,
        WikiAdministratorTestData $data,
        ?PrincipalGroup $principalGroup,
    ): void {
        $dependencies->accountRepository->shouldReceive('findByEmail')->once()->with($data->email)->andReturn($data->account);
        $dependencies->roleRepository->shouldReceive('findSystemByName')->once()
            ->with('ADMINISTRATOR')->andReturn($data->administratorRole);
        $dependencies->principalGroupRepository->shouldReceive('findByAccountIdAndName')->once()
            ->with($data->account->accountIdentifier(), 'Operations Wiki Administrators')
            ->andReturn($principalGroup);
    }

    private function subject(RevokeDependencies $dependencies): RevokeWikiAdministrator
    {
        $this->app()->instance(IdentityRepositoryInterface::class, $dependencies->identityRepository);
        $this->app()->instance(PrincipalRepositoryInterface::class, $dependencies->principalRepository);

        $this->app()->instance(AccountRepositoryInterface::class, $dependencies->accountRepository);
        $this->app()->instance(PrincipalGroupRepositoryInterface::class, $dependencies->principalGroupRepository);
        $this->app()->instance(RoleRepositoryInterface::class, $dependencies->roleRepository);

        return $this->app()->make(RevokeWikiAdministratorInterface::class);
    }
}

readonly class RevokeDependencies
{
    public function __construct(
        public MockInterface&IdentityRepositoryInterface $identityRepository,
        public MockInterface&AccountRepositoryInterface $accountRepository,
        public MockInterface&PrincipalRepositoryInterface $principalRepository,
        public MockInterface&PrincipalGroupRepositoryInterface $principalGroupRepository,
        public MockInterface&RoleRepositoryInterface $roleRepository,
    ) {
    }

    public static function create(): self
    {
        /** @var MockInterface&IdentityRepositoryInterface $identityRepository */
        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        /** @var MockInterface&AccountRepositoryInterface $accountRepository */
        $accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        /** @var MockInterface&PrincipalRepositoryInterface $principalRepository */
        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        /** @var MockInterface&PrincipalGroupRepositoryInterface $principalGroupRepository */
        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        /** @var MockInterface&RoleRepositoryInterface $roleRepository */
        $roleRepository = Mockery::mock(RoleRepositoryInterface::class);

        return new self(
            $identityRepository,
            $accountRepository,
            $principalRepository,
            $principalGroupRepository,
            $roleRepository,
        );
    }
}
