<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Application\UseCase\Command\CreatePrincipal;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
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

        $output = new CreatePrincipalOutput();
        $this->app->make(CreatePrincipalInterface::class)->process(
            new CreatePrincipalInput($identityIdentifier, $accountIdentifier),
            $output,
        );

        $this->assertSame((string) $principalIdentifier, $output->toArray()['principalIdentifier']);
        $this->assertTrue($defaultPrincipalGroup->hasRole($roleIdentifier));
        $this->assertTrue($defaultPrincipalGroup->hasMember($principalIdentifier));
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

        $this->expectException(PrincipalAlreadyExistsException::class);

        $this->app->make(CreatePrincipalInterface::class)->process(
            new CreatePrincipalInput($identityIdentifier, $accountIdentifier),
            new CreatePrincipalOutput(),
        );
    }
}
