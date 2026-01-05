<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Application\EventHandler;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Identity\Domain\Event\DelegatedIdentityCreated;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Principal\Application\EventHandler\DelegatedIdentityCreatedHandler;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Factory\PrincipalFactoryInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\ValueObject\Role;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class DelegatedIdentityCreatedHandlerTest extends TestCase
{
    /**
     * 正しくDIが動作していること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalFactory = Mockery::mock(PrincipalFactoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(PrincipalFactoryInterface::class, $principalFactory);

        $handler = $this->app->make(DelegatedIdentityCreatedHandler::class);

        $this->assertInstanceOf(DelegatedIdentityCreatedHandler::class, $handler);
    }

    /**
     * 正常系: 委譲Identityが作成された時に委譲Principalが作成されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testHandleCreatesDelegatedPrincipal(): void
    {
        $delegationId = new DelegationIdentifier(StrTestHelper::generateUuid());
        $delegatedIdentityId = new IdentityIdentifier(StrTestHelper::generateUuid());
        $originalIdentityId = new IdentityIdentifier(StrTestHelper::generateUuid());
        $createdAt = new DateTimeImmutable();

        $event = new DelegatedIdentityCreated(
            $delegationId,
            $delegatedIdentityId,
            $originalIdentityId,
            $createdAt,
        );

        $originalPrincipal = $this->createPrincipal(
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            $originalIdentityId,
        );
        $delegatedPrincipal = $this->createDelegatedPrincipal(
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            $delegatedIdentityId,
            $delegationId,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findByIdentityIdentifier')
            ->once()
            ->with($originalIdentityId)
            ->andReturn($originalPrincipal);
        $principalRepository->shouldReceive('save')
            ->once()
            ->with($delegatedPrincipal);

        $principalFactory = Mockery::mock(PrincipalFactoryInterface::class);
        $principalFactory->shouldReceive('createDelegatedPrincipal')
            ->once()
            ->with($originalPrincipal, $delegationId, $delegatedIdentityId)
            ->andReturn($delegatedPrincipal);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(PrincipalFactoryInterface::class, $principalFactory);

        $handler = $this->app->make(DelegatedIdentityCreatedHandler::class);

        $handler->handle($event);
    }

    /**
     * 異常系: 元のPrincipalが見つからない場合は何もしないこと.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testHandleReturnsEarlyWhenOriginalPrincipalNotFound(): void
    {
        $delegationId = new DelegationIdentifier(StrTestHelper::generateUuid());
        $delegatedIdentityId = new IdentityIdentifier(StrTestHelper::generateUuid());
        $originalIdentityId = new IdentityIdentifier(StrTestHelper::generateUuid());
        $createdAt = new DateTimeImmutable();

        $event = new DelegatedIdentityCreated(
            $delegationId,
            $delegatedIdentityId,
            $originalIdentityId,
            $createdAt,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findByIdentityIdentifier')
            ->once()
            ->with($originalIdentityId)
            ->andReturnNull();
        $principalRepository->shouldNotReceive('save');

        $principalFactory = Mockery::mock(PrincipalFactoryInterface::class);
        $principalFactory->shouldNotReceive('createDelegatedPrincipal');

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(PrincipalFactoryInterface::class, $principalFactory);

        $handler = $this->app->make(DelegatedIdentityCreatedHandler::class);

        $handler->handle($event);
    }

    private function createPrincipal(
        PrincipalIdentifier $principalIdentifier,
        IdentityIdentifier $identityIdentifier,
    ): Principal {
        return new Principal(
            $principalIdentifier,
            $identityIdentifier,
            Role::ADMINISTRATOR,
            null,
            [],
            [],
        );
    }

    private function createDelegatedPrincipal(
        PrincipalIdentifier $principalIdentifier,
        IdentityIdentifier $identityIdentifier,
        DelegationIdentifier $delegationIdentifier,
    ): Principal {
        return new Principal(
            $principalIdentifier,
            $identityIdentifier,
            Role::ADMINISTRATOR,
            null,
            [],
            [],
            $delegationIdentifier,
        );
    }
}
