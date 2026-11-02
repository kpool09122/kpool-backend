<?php

declare(strict_types=1);

namespace Tests\Analyze\Principal\Application\UseCase\Command\CreatePrincipal;

use Mockery;
use PHPUnit\Framework\TestCase;
use Source\Analyze\Principal\Application\Exception\AccountMembershipRequiredException;
use Source\Analyze\Principal\Application\Service\AccountMembershipServiceInterface;
use Source\Analyze\Principal\Application\UseCase\Command\CreatePrincipal\CreatePrincipal;
use Source\Analyze\Principal\Application\UseCase\Command\CreatePrincipal\CreatePrincipalInput;
use Source\Analyze\Principal\Application\UseCase\Command\CreatePrincipal\CreatePrincipalOutput;
use Source\Analyze\Principal\Domain\Exception\PrincipalAlreadyExistsException;
use Source\Analyze\Principal\Domain\Factory\PrincipalFactoryInterface;
use Source\Analyze\Principal\Domain\Factory\PrincipalGroupFactoryInterface;
use Source\Analyze\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Analyze\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Analyze\Principal\Domain\Repository\RoleRepositoryInterface;
use Tests\Helper\AnalyzePrincipalTestData;

class CreatePrincipalTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testCreatesPrincipalInGeneralWithDefaultRole(): void
    {
        $data = AnalyzePrincipalTestData::create();
        /** @var AccountMembershipServiceInterface&Mockery\MockInterface $membership */
        $membership = Mockery::mock(AccountMembershipServiceInterface::class);
        $membership->shouldReceive('belongsToActiveAccount')->once()->with($data->principal->identityIdentifier(), $data->principal->accountIdentifier())->andReturnTrue();
        /** @var PrincipalRepositoryInterface&Mockery\MockInterface $principals */
        $principals = Mockery::mock(PrincipalRepositoryInterface::class);
        $principals->shouldReceive('findByIdentityIdentifierAndAccountIdentifier')->once()->andReturnNull();
        $principals->shouldReceive('save')->once()->with($data->principal);
        /** @var PrincipalFactoryInterface&Mockery\MockInterface $factory */
        $factory = Mockery::mock(PrincipalFactoryInterface::class);
        $factory->shouldReceive('create')->once()->with($data->principal->identityIdentifier(), $data->principal->accountIdentifier())->andReturn($data->principal);
        /** @var PrincipalGroupRepositoryInterface&Mockery\MockInterface $groups */
        $groups = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $groups->shouldReceive('findDefaultByAccountIdentifier')->once()->with($data->principal->accountIdentifier())->andReturnNull();
        $groups->shouldReceive('save')->once()->with(Mockery::on(static fn ($group): bool => $group === $data->group && $group->hasMember($data->principal->principalIdentifier()) && $group->hasRole($data->role->roleIdentifier())));
        /** @var PrincipalGroupFactoryInterface&Mockery\MockInterface $groupFactory */
        $groupFactory = Mockery::mock(PrincipalGroupFactoryInterface::class);
        $groupFactory->shouldReceive('create')->once()->with('General', [$data->role->roleIdentifier()], $data->principal->accountIdentifier(), true)->andReturn($data->group);
        /** @var RoleRepositoryInterface&Mockery\MockInterface $roles */
        $roles = Mockery::mock(RoleRepositoryInterface::class);
        $roles->shouldReceive('findSystemByName')->once()->with('General')->andReturn($data->role);
        $data->group->addRole($data->role);
        $output = new CreatePrincipalOutput();
        (new CreatePrincipal($principals, $factory, $groups, $groupFactory, $roles, $membership))->process(new CreatePrincipalInput($data->principal->identityIdentifier(), $data->principal->accountIdentifier()), $output);
        self::assertSame($data->principal, $output->principal());
    }

    public function testRejectsOtherAccountBeforeCreatingAnything(): void
    {
        $data = AnalyzePrincipalTestData::create();
        $membership = $this->createMock(AccountMembershipServiceInterface::class);
        $membership->expects(self::once())->method('belongsToActiveAccount')->willReturn(false);
        $principals = $this->createMock(PrincipalRepositoryInterface::class);
        $principals->expects(self::never())->method('save');
        $principals->expects(self::never())->method('findByIdentityIdentifierAndAccountIdentifier');
        $this->expectException(AccountMembershipRequiredException::class);
        (new CreatePrincipal($principals, $this->createStub(PrincipalFactoryInterface::class), $this->createStub(PrincipalGroupRepositoryInterface::class), $this->createStub(PrincipalGroupFactoryInterface::class), $this->createStub(RoleRepositoryInterface::class), $membership))->process(new CreatePrincipalInput($data->principal->identityIdentifier(), $data->principal->accountIdentifier()), new CreatePrincipalOutput());
    }

    public function testRejectsDuplicateWithoutRestoringSuspendedPermissions(): void
    {
        $data = AnalyzePrincipalTestData::create();
        $membership = $this->createStub(AccountMembershipServiceInterface::class);
        $membership->method('belongsToActiveAccount')->willReturn(true);
        $principals = $this->createMock(PrincipalRepositoryInterface::class);
        $principals->expects(self::once())->method('findByIdentityIdentifierAndAccountIdentifier')->willReturn($data->principal);
        $principals->expects(self::never())->method('save');
        $groups = $this->createMock(PrincipalGroupRepositoryInterface::class);
        $groups->expects(self::never())->method('save');
        $this->expectException(PrincipalAlreadyExistsException::class);
        (new CreatePrincipal($principals, $this->createStub(PrincipalFactoryInterface::class), $groups, $this->createStub(PrincipalGroupFactoryInterface::class), $this->createStub(RoleRepositoryInterface::class), $membership))->process(new CreatePrincipalInput($data->principal->identityIdentifier(), $data->principal->accountIdentifier()), new CreatePrincipalOutput());
    }
}
