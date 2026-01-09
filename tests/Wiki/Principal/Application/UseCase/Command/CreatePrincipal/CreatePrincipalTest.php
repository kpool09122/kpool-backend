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
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Entity\PrincipalGroup;
use Source\Wiki\Principal\Domain\Exception\PrincipalAlreadyExistsException;
use Source\Wiki\Principal\Domain\Factory\PrincipalFactoryInterface;
use Source\Wiki\Principal\Domain\Factory\PrincipalGroupFactoryInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\Role;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class CreatePrincipalTest extends TestCase
{
    /**
     * 正常系: 正しくプリンシパルを作成できること（Default PrincipalGroup が存在しない場合）.
     *
     * @return void
     * @throws PrincipalAlreadyExistsException
     * @throws BindingResolutionException
     */
    public function testProcess(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principalGroupIdentifier = new PrincipalGroupIdentifier(StrTestHelper::generateUuid());
        $role = Role::NONE;

        $input = new CreatePrincipalInput(
            $identityIdentifier,
            $accountIdentifier,
        );

        $expectedPrincipal = new Principal(
            $principalIdentifier,
            $identityIdentifier,
            $role,
            null,
            [],
            [],
        );

        $defaultPrincipalGroup = new PrincipalGroup(
            $principalGroupIdentifier,
            $accountIdentifier,
            'Default',
            true,
            new DateTimeImmutable(),
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findByIdentityIdentifier')
            ->with($identityIdentifier)
            ->once()
            ->andReturn(null);

        $principalFactory = Mockery::mock(PrincipalFactoryInterface::class);
        $principalFactory->shouldReceive('create')
            ->with($identityIdentifier)
            ->once()
            ->andReturn($expectedPrincipal);

        $principalRepository->shouldReceive('save')
            ->with($expectedPrincipal)
            ->once();

        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $principalGroupRepository->shouldReceive('findDefaultByAccountId')
            ->with($accountIdentifier)
            ->once()
            ->andReturn(null);

        $principalGroupFactory = Mockery::mock(PrincipalGroupFactoryInterface::class);
        $principalGroupFactory->shouldReceive('create')
            ->with($accountIdentifier, 'Default', true)
            ->once()
            ->andReturn($defaultPrincipalGroup);

        $principalGroupRepository->shouldReceive('save')
            ->with($defaultPrincipalGroup)
            ->twice();

        $this->app->instance(PrincipalFactoryInterface::class, $principalFactory);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(PrincipalGroupFactoryInterface::class, $principalGroupFactory);
        $this->app->instance(PrincipalGroupRepositoryInterface::class, $principalGroupRepository);
        $useCase = $this->app->make(CreatePrincipalInterface::class);
        $result = $useCase->process($input);

        $this->assertSame((string) $principalIdentifier, (string) $result->principalIdentifier());
        $this->assertSame((string) $identityIdentifier, (string) $result->identityIdentifier());
        $this->assertSame($role, $result->role());
        $this->assertTrue($defaultPrincipalGroup->hasMember($principalIdentifier));
    }

    /**
     * 正常系: Default PrincipalGroup が既に存在する場合、既存のグループに Principal を追加すること.
     *
     * @return void
     * @throws PrincipalAlreadyExistsException
     * @throws BindingResolutionException
     */
    public function testProcessWithExistingDefaultPrincipalGroup(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principalGroupIdentifier = new PrincipalGroupIdentifier(StrTestHelper::generateUuid());
        $role = Role::NONE;

        $input = new CreatePrincipalInput(
            $identityIdentifier,
            $accountIdentifier,
        );

        $expectedPrincipal = new Principal(
            $principalIdentifier,
            $identityIdentifier,
            $role,
            null,
            [],
            [],
        );

        $existingDefaultPrincipalGroup = new PrincipalGroup(
            $principalGroupIdentifier,
            $accountIdentifier,
            'Default',
            true,
            new DateTimeImmutable(),
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findByIdentityIdentifier')
            ->with($identityIdentifier)
            ->once()
            ->andReturn(null);

        $principalFactory = Mockery::mock(PrincipalFactoryInterface::class);
        $principalFactory->shouldReceive('create')
            ->with($identityIdentifier)
            ->once()
            ->andReturn($expectedPrincipal);

        $principalRepository->shouldReceive('save')
            ->with($expectedPrincipal)
            ->once();

        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $principalGroupRepository->shouldReceive('findDefaultByAccountId')
            ->with($accountIdentifier)
            ->once()
            ->andReturn($existingDefaultPrincipalGroup);

        $principalGroupFactory = Mockery::mock(PrincipalGroupFactoryInterface::class);
        $principalGroupFactory->shouldNotReceive('create');

        $principalGroupRepository->shouldReceive('save')
            ->with($existingDefaultPrincipalGroup)
            ->once();

        $this->app->instance(PrincipalFactoryInterface::class, $principalFactory);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(PrincipalGroupFactoryInterface::class, $principalGroupFactory);
        $this->app->instance(PrincipalGroupRepositoryInterface::class, $principalGroupRepository);
        $useCase = $this->app->make(CreatePrincipalInterface::class);
        $result = $useCase->process($input);

        $this->assertSame((string) $principalIdentifier, (string) $result->principalIdentifier());
        $this->assertSame((string) $identityIdentifier, (string) $result->identityIdentifier());
        $this->assertSame($role, $result->role());
        $this->assertTrue($existingDefaultPrincipalGroup->hasMember($principalIdentifier));
    }

    /**
     * 異常系: すでに生成済みのプリンシパルを作成しようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testProcessThrowsExceptionWhenPrincipalAlreadyExists(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $role = Role::NONE;

        $input = new CreatePrincipalInput(
            $identityIdentifier,
            $accountIdentifier,
        );

        $existingPrincipal = new Principal(
            $principalIdentifier,
            $identityIdentifier,
            $role,
            null,
            [],
            [],
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findByIdentityIdentifier')
            ->with($identityIdentifier)
            ->once()
            ->andReturn($existingPrincipal);
        $principalRepository->shouldNotReceive('save');

        $principalFactory = Mockery::mock(PrincipalFactoryInterface::class);
        $principalFactory->shouldNotReceive('create');

        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $principalGroupRepository->shouldNotReceive('findDefaultByAccountId');
        $principalGroupRepository->shouldNotReceive('save');

        $principalGroupFactory = Mockery::mock(PrincipalGroupFactoryInterface::class);
        $principalGroupFactory->shouldNotReceive('create');

        $this->expectException(PrincipalAlreadyExistsException::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(PrincipalGroupRepositoryInterface::class, $principalGroupRepository);
        $this->app->instance(PrincipalGroupFactoryInterface::class, $principalGroupFactory);
        $useCase = $this->app->make(CreatePrincipalInterface::class);
        $useCase->process($input);
    }
}
