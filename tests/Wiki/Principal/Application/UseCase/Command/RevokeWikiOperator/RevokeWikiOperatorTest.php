<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Application\UseCase\Command\RevokeWikiOperator;

use Mockery;
use Mockery\MockInterface;
use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;
use Source\Wiki\Principal\Application\Exception\OperatorRoleNotAttachedException;
use Source\Wiki\Principal\Application\Exception\PrincipalGroupNotFoundException;
use Source\Wiki\Principal\Application\Exception\SystemRoleNotFoundException;
use Source\Wiki\Principal\Application\UseCase\Command\RevokeWikiOperator\RevokeWikiOperator;
use Source\Wiki\Principal\Application\UseCase\Command\RevokeWikiOperator\RevokeWikiOperatorInput;
use Source\Wiki\Principal\Application\UseCase\Command\RevokeWikiOperator\RevokeWikiOperatorInterface;
use Source\Wiki\Principal\Application\UseCase\Command\RevokeWikiOperator\RevokeWikiOperatorOutput;
use Source\Wiki\Principal\Domain\Entity\PrincipalGroup;
use Source\Wiki\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\Helper\WikiOperatorTestData;
use Tests\TestCase;

class RevokeWikiOperatorTest extends TestCase
{
    public function testDeletesTheWholeTargetAccountOperatorGroupIncludingTheLastAdministrator(): void
    {
        $dependencies = RevokeDependencies::create();
        $data = WikiOperatorTestData::create();
        $data->operatorGroup->addRole($data->operatorRole);
        $data->operatorGroup->addMember($data->wikiPrincipal->principalIdentifier());
        $this->expectTarget($dependencies, $data, $data->operatorGroup);
        $dependencies->principalGroupRepository->shouldReceive('delete')->once()->with($data->operatorGroup);
        $dependencies->principalRepository->shouldNotReceive('save');

        $this->subject($dependencies)->process(
            new RevokeWikiOperatorInput($data->email),
            new RevokeWikiOperatorOutput(),
        );

        $this->assertSame(1, $data->operatorGroup->memberCount());
    }

    public function testDoesNotCheckAccountOperationsMembershipBeforeRevocation(): void
    {
        $dependencies = RevokeDependencies::create();
        $data = WikiOperatorTestData::create();
        $data->operatorGroup->addRole($data->operatorRole);
        $data->operatorGroup->addMember($data->wikiPrincipal->principalIdentifier());
        $this->expectTarget($dependencies, $data, $data->operatorGroup);
        $dependencies->principalGroupRepository->shouldReceive('delete')->once();

        $this->subject($dependencies)->process(
            new RevokeWikiOperatorInput($data->email),
            new RevokeWikiOperatorOutput(),
        );

    }

    public function testScopesGroupLookupToTheTargetAccount(): void
    {
        $dependencies = RevokeDependencies::create();
        $data = WikiOperatorTestData::create();
        $data->operatorGroup->addRole($data->operatorRole);
        $data->operatorGroup->addMember($data->wikiPrincipal->principalIdentifier());
        $dependencies->accountRepository->shouldReceive('findByEmail')->once()->andReturn($data->account);
        $dependencies->roleRepository->shouldReceive('findSystemByName')->once()->andReturn($data->operatorRole);
        $dependencies->principalGroupRepository->shouldReceive('findByAccountIdAndName')->once()
            ->with($data->account->accountIdentifier(), 'Operations Wiki Operators')
            ->andReturn($data->operatorGroup);
        $dependencies->principalGroupRepository->shouldReceive('delete')->once()->with($data->operatorGroup);

        $this->subject($dependencies)->process(
            new RevokeWikiOperatorInput($data->email),
            new RevokeWikiOperatorOutput(),
        );
    }

    public function testThrowsWhenAccountDoesNotExist(): void
    {
        $dependencies = RevokeDependencies::create();
        $data = WikiOperatorTestData::create();
        $dependencies->accountRepository->shouldReceive('findByEmail')->once()->andReturnNull();

        $this->expectException(AccountNotFoundException::class);
        $this->subject($dependencies)->process(new RevokeWikiOperatorInput($data->email), new RevokeWikiOperatorOutput());
    }

    public function testThrowsWhenAdministratorSystemRoleDoesNotExist(): void
    {
        $dependencies = RevokeDependencies::create();
        $data = WikiOperatorTestData::create();
        $dependencies->accountRepository->shouldReceive('findByEmail')->once()->andReturn($data->account);
        $dependencies->roleRepository->shouldReceive('findSystemByName')->once()->andReturnNull();

        $this->expectException(SystemRoleNotFoundException::class);
        $this->subject($dependencies)->process(new RevokeWikiOperatorInput($data->email), new RevokeWikiOperatorOutput());
    }

    public function testThrowsWhenOperatorGroupDoesNotExistIncludingRepeatedRevocation(): void
    {
        $dependencies = RevokeDependencies::create();
        $data = WikiOperatorTestData::create();
        $this->expectTarget($dependencies, $data, null);
        $dependencies->principalGroupRepository->shouldNotReceive('delete');

        $this->expectException(PrincipalGroupNotFoundException::class);
        $this->subject($dependencies)->process(new RevokeWikiOperatorInput($data->email), new RevokeWikiOperatorOutput());
    }

    public function testThrowsWhenOperatorRoleIsNotAttached(): void
    {
        $dependencies = RevokeDependencies::create();
        $data = WikiOperatorTestData::create();
        $data->operatorGroup->addMember($data->wikiPrincipal->principalIdentifier());
        $this->expectTarget($dependencies, $data, $data->operatorGroup);
        $dependencies->principalGroupRepository->shouldNotReceive('delete');

        $this->expectException(OperatorRoleNotAttachedException::class);
        $this->subject($dependencies)->process(new RevokeWikiOperatorInput($data->email), new RevokeWikiOperatorOutput());
    }

    public function testDeletesGroupAfterOriginalPrincipalWasRemoved(): void
    {
        $dependencies = RevokeDependencies::create();
        $data = WikiOperatorTestData::create();
        $data->operatorGroup->addRole($data->operatorRole);
        $data->operatorGroup->addMember(new PrincipalIdentifier(StrTestHelper::generateUuid()));
        $this->assertFalse($data->operatorGroup->hasMember($data->wikiPrincipal->principalIdentifier()));
        $this->expectTarget($dependencies, $data, $data->operatorGroup);
        $dependencies->principalGroupRepository->shouldReceive('delete')->once()->with($data->operatorGroup);

        $this->subject($dependencies)->process(new RevokeWikiOperatorInput($data->email), new RevokeWikiOperatorOutput());
    }

    private function expectTarget(
        RevokeDependencies $dependencies,
        WikiOperatorTestData $data,
        ?PrincipalGroup $principalGroup,
    ): void {
        $dependencies->accountRepository->shouldReceive('findByEmail')->once()->with($data->email)->andReturn($data->account);
        $dependencies->roleRepository->shouldReceive('findSystemByName')->once()
            ->with('Operator')->andReturn($data->operatorRole);
        $dependencies->principalGroupRepository->shouldReceive('findByAccountIdAndName')->once()
            ->with($data->account->accountIdentifier(), 'Operations Wiki Operators')
            ->andReturn($principalGroup);
    }

    private function subject(RevokeDependencies $dependencies): RevokeWikiOperator
    {
        $this->app()->instance(IdentityRepositoryInterface::class, $dependencies->identityRepository);
        $this->app()->instance(PrincipalRepositoryInterface::class, $dependencies->principalRepository);

        $this->app()->instance(AccountRepositoryInterface::class, $dependencies->accountRepository);
        $this->app()->instance(PrincipalGroupRepositoryInterface::class, $dependencies->principalGroupRepository);
        $this->app()->instance(RoleRepositoryInterface::class, $dependencies->roleRepository);

        return $this->app()->make(RevokeWikiOperatorInterface::class);
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
