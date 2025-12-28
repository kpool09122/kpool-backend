<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Application\UseCase\Command\CreatePrincipal;

use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\AccessControl\Application\Exception\PrincipalAlreadyExistsException;
use Source\Wiki\Principal\Application\UseCase\Command\CreatePrincipal\CreatePrincipalInput;
use Source\Wiki\Principal\Application\UseCase\Command\CreatePrincipal\CreatePrincipalInterface;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Factory\PrincipalFactoryInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\ValueObject\Role;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class CreatePrincipalTest extends TestCase
{
    /**
     * 正常系: 正しくプリンシパルを作成できること.
     *
     * @return void
     * @throws PrincipalAlreadyExistsException
     * @throws BindingResolutionException
     */
    public function testProcess(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUlid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $role = Role::NONE;

        $input = new CreatePrincipalInput(
            $identityIdentifier,
        );

        $expectedPrincipal = new Principal(
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
            ->andReturn(null);

        $principalFactory = Mockery::mock(PrincipalFactoryInterface::class);
        $principalFactory->shouldReceive('create')
            ->with($identityIdentifier)
            ->once()
            ->andReturn($expectedPrincipal);

        $principalRepository->shouldReceive('save')
            ->with($expectedPrincipal)
            ->once();

        $this->app->instance(PrincipalFactoryInterface::class, $principalFactory);
        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $useCase = $this->app->make(CreatePrincipalInterface::class);
        $result = $useCase->process($input);

        $this->assertSame((string) $principalIdentifier, (string) $result->principalIdentifier());
        $this->assertSame((string) $identityIdentifier, (string) $result->identityIdentifier());
        $this->assertSame($role, $result->role());
    }

    /**
     * 異常系: すでに生成済みのプリンシパルを作成しようとした場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testProcessThrowsExceptionWhenPrincipalAlreadyExists(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUlid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUlid());
        $role = Role::NONE;

        $input = new CreatePrincipalInput(
            $identityIdentifier,
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

        $this->expectException(PrincipalAlreadyExistsException::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $useCase = $this->app->make(CreatePrincipalInterface::class);
        $useCase->process($input);
    }
}
