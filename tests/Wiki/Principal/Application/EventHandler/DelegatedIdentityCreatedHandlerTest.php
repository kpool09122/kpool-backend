<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Application\EventHandler;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Identity\Domain\Event\DelegatedIdentityCreated;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Principal\Application\EventHandler\DelegatedIdentityCreatedHandler;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Entity\PrincipalGroup;
use Source\Wiki\Principal\Domain\Factory\PrincipalFactoryInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
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
        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(PrincipalFactoryInterface::class, $principalFactory);
        $this->app->instance(PrincipalGroupRepositoryInterface::class, $principalGroupRepository);

        $handler = $this->app->make(DelegatedIdentityCreatedHandler::class);

        $this->assertInstanceOf(DelegatedIdentityCreatedHandler::class, $handler);
    }

    /**
     * 正常系: 委譲Identityが作成された時に委譲Principalが作成され、同じPrincipalGroupに追加されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testHandleCreatesDelegatedPrincipalAndAddsToSamePrincipalGroups(): void
    {
        $delegationId = new DelegationIdentifier(StrTestHelper::generateUuid());
        $delegatedIdentityId = new IdentityIdentifier(StrTestHelper::generateUuid());
        $originalIdentityId = new IdentityIdentifier(StrTestHelper::generateUuid());
        $createdAt = new DateTimeImmutable();
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $otherAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());

        $event = new DelegatedIdentityCreated(
            $delegationId,
            $delegatedIdentityId,
            $originalIdentityId,
            $createdAt,
        );

        $originalPrincipalId = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $otherOriginalPrincipalId = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $delegatedPrincipalId = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $otherDelegatedPrincipalId = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $originalPrincipal = $this->createPrincipal(
            $originalPrincipalId,
            $originalIdentityId,
            $accountIdentifier,
        );
        $otherOriginalPrincipal = $this->createPrincipal(
            $otherOriginalPrincipalId,
            $originalIdentityId,
            $otherAccountIdentifier,
        );
        $delegatedPrincipal = $this->createDelegatedPrincipal(
            $delegatedPrincipalId,
            $delegatedIdentityId,
            $delegationId,
            $accountIdentifier,
        );
        $otherDelegatedPrincipal = $this->createDelegatedPrincipal(
            $otherDelegatedPrincipalId,
            $delegatedIdentityId,
            $delegationId,
            $otherAccountIdentifier,
        );

        $principalGroup = new PrincipalGroup(
            new PrincipalGroupIdentifier(StrTestHelper::generateUuid()),
            $accountIdentifier,
            'Default',
            true,
            new DateTimeImmutable(),
        );
        $principalGroup->addMember($originalPrincipalId);
        $otherPrincipalGroup = new PrincipalGroup(
            new PrincipalGroupIdentifier(StrTestHelper::generateUuid()),
            $otherAccountIdentifier,
            'Default',
            true,
            new DateTimeImmutable(),
        );
        $otherPrincipalGroup->addMember($otherOriginalPrincipalId);

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findByIdentityIdentifier')
            ->once()
            ->with($originalIdentityId)
            ->andReturn([$originalPrincipal, $otherOriginalPrincipal]);
        $principalRepository->shouldReceive('save')
            ->once()
            ->with($delegatedPrincipal);
        $principalRepository->shouldReceive('save')
            ->once()
            ->with($otherDelegatedPrincipal);

        $principalFactory = Mockery::mock(PrincipalFactoryInterface::class);
        $principalFactory->shouldReceive('createDelegatedPrincipal')
            ->once()
            ->with($originalPrincipal, $delegationId, $delegatedIdentityId)
            ->andReturn($delegatedPrincipal);
        $principalFactory->shouldReceive('createDelegatedPrincipal')
            ->once()
            ->with($otherOriginalPrincipal, $delegationId, $delegatedIdentityId)
            ->andReturn($otherDelegatedPrincipal);

        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $principalGroupRepository->shouldReceive('findByPrincipalId')
            ->once()
            ->with($originalPrincipalId)
            ->andReturn([$principalGroup]);
        $principalGroupRepository->shouldReceive('findByPrincipalId')
            ->once()
            ->with($otherOriginalPrincipalId)
            ->andReturn([$otherPrincipalGroup]);
        $principalGroupRepository->shouldReceive('save')
            ->once()
            ->with($principalGroup);
        $principalGroupRepository->shouldReceive('save')
            ->once()
            ->with($otherPrincipalGroup);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(PrincipalFactoryInterface::class, $principalFactory);
        $this->app->instance(PrincipalGroupRepositoryInterface::class, $principalGroupRepository);

        $handler = $this->app->make(DelegatedIdentityCreatedHandler::class);

        $handler->handle($event);

        $this->assertTrue($principalGroup->hasMember($delegatedPrincipalId));
        $this->assertTrue($otherPrincipalGroup->hasMember($otherDelegatedPrincipalId));
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
            ->andReturn([]);
        $principalRepository->shouldNotReceive('save');

        $principalFactory = Mockery::mock(PrincipalFactoryInterface::class);
        $principalFactory->shouldNotReceive('createDelegatedPrincipal');

        $principalGroupRepository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $principalGroupRepository->shouldNotReceive('findByPrincipalId');
        $principalGroupRepository->shouldNotReceive('save');

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app->instance(PrincipalFactoryInterface::class, $principalFactory);
        $this->app->instance(PrincipalGroupRepositoryInterface::class, $principalGroupRepository);

        $handler = $this->app->make(DelegatedIdentityCreatedHandler::class);

        $handler->handle($event);
    }

    private function createPrincipal(
        PrincipalIdentifier $principalIdentifier,
        IdentityIdentifier $identityIdentifier,
        AccountIdentifier $accountIdentifier,
    ): Principal {
        return new Principal(
            $principalIdentifier,
            $identityIdentifier,
            $accountIdentifier,
        );
    }

    private function createDelegatedPrincipal(
        PrincipalIdentifier $principalIdentifier,
        IdentityIdentifier $identityIdentifier,
        DelegationIdentifier $delegationIdentifier,
        AccountIdentifier $accountIdentifier,
    ): Principal {
        return new Principal(
            $principalIdentifier,
            $identityIdentifier,
            $accountIdentifier,
            $delegationIdentifier,
        );
    }
}
