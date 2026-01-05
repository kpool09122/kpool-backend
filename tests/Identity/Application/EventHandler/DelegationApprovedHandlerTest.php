<?php

declare(strict_types=1);

namespace Tests\Identity\Application\EventHandler;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Account\Domain\Event\DelegationApproved;
use Source\Identity\Application\EventHandler\DelegationApprovedHandler;
use Source\Identity\Domain\Entity\Identity;
use Source\Identity\Domain\Event\DelegatedIdentityCreated;
use Source\Identity\Domain\Exception\IdentityNotFoundException;
use Source\Identity\Domain\Factory\IdentityFactoryInterface;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;
use Source\Identity\Domain\ValueObject\HashedPassword;
use Source\Identity\Domain\ValueObject\PlainPassword;
use Source\Identity\Domain\ValueObject\UserName;
use Source\Shared\Application\Service\Event\EventDispatcherInterface;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class DelegationApprovedHandlerTest extends TestCase
{
    /**
     * 正しくDIが動作していること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        $identityFactory = Mockery::mock(IdentityFactoryInterface::class);
        $eventDispatcher = Mockery::mock(EventDispatcherInterface::class);

        $this->app->instance(IdentityRepositoryInterface::class, $identityRepository);
        $this->app->instance(IdentityFactoryInterface::class, $identityFactory);
        $this->app->instance(EventDispatcherInterface::class, $eventDispatcher);

        $handler = $this->app->make(DelegationApprovedHandler::class);

        $this->assertInstanceOf(DelegationApprovedHandler::class, $handler);
    }

    /**
     * 正常系: 委譲が承認された時に委譲Identityが作成され、イベントが発行されること.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws IdentityNotFoundException
     */
    public function testHandleCreatesDelegatedIdentity(): void
    {
        $delegationId = new DelegationIdentifier(StrTestHelper::generateUuid());
        $delegateId = new IdentityIdentifier(StrTestHelper::generateUuid());
        $delegatorId = new IdentityIdentifier(StrTestHelper::generateUuid());
        $approvedAt = new DateTimeImmutable();

        $event = new DelegationApproved(
            $delegationId,
            $delegateId,
            $delegatorId,
            $approvedAt,
        );

        $originalIdentity = $this->createIdentity($delegatorId);
        $delegatedIdentityId = new IdentityIdentifier(StrTestHelper::generateUuid());
        $delegatedIdentity = $this->createDelegatedIdentity(
            $delegatedIdentityId,
            $delegationId,
            $delegatorId,
        );

        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        $identityRepository->shouldReceive('findById')
            ->once()
            ->with($delegatorId)
            ->andReturn($originalIdentity);
        $identityRepository->shouldReceive('save')
            ->once()
            ->with($delegatedIdentity);

        $identityFactory = Mockery::mock(IdentityFactoryInterface::class);
        $identityFactory->shouldReceive('createDelegatedIdentity')
            ->once()
            ->with($originalIdentity, $delegationId)
            ->andReturn($delegatedIdentity);

        $eventDispatcher = Mockery::mock(EventDispatcherInterface::class);
        $eventDispatcher->shouldReceive('dispatch')
            ->once()
            ->with(Mockery::on(static function ($dispatchedEvent) use ($delegationId, $delegatedIdentityId, $delegatorId) {
                return $dispatchedEvent instanceof DelegatedIdentityCreated
                    && (string) $dispatchedEvent->delegationIdentifier() === (string) $delegationId
                    && (string) $dispatchedEvent->delegatedIdentityIdentifier() === (string) $delegatedIdentityId
                    && (string) $dispatchedEvent->originalIdentityIdentifier() === (string) $delegatorId;
            }))
            ->andReturnNull();

        $this->app->instance(IdentityRepositoryInterface::class, $identityRepository);
        $this->app->instance(IdentityFactoryInterface::class, $identityFactory);
        $this->app->instance(EventDispatcherInterface::class, $eventDispatcher);

        $handler = $this->app->make(DelegationApprovedHandler::class);

        $handler->handle($event);
    }

    /**
     * 異常系: 元のIdentityが見つからない場合に例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testHandleThrowsIdentityNotFoundExceptionWhenOriginalIdentityNotFound(): void
    {
        $delegationId = new DelegationIdentifier(StrTestHelper::generateUuid());
        $delegateId = new IdentityIdentifier(StrTestHelper::generateUuid());
        $delegatorId = new IdentityIdentifier(StrTestHelper::generateUuid());
        $approvedAt = new DateTimeImmutable();

        $event = new DelegationApproved(
            $delegationId,
            $delegateId,
            $delegatorId,
            $approvedAt,
        );

        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        $identityRepository->shouldReceive('findById')
            ->once()
            ->with($delegatorId)
            ->andReturnNull();
        $identityRepository->shouldNotReceive('save');

        $identityFactory = Mockery::mock(IdentityFactoryInterface::class);
        $identityFactory->shouldNotReceive('createDelegatedIdentity');

        $eventDispatcher = Mockery::mock(EventDispatcherInterface::class);
        $eventDispatcher->shouldNotReceive('dispatch');

        $this->app->instance(IdentityRepositoryInterface::class, $identityRepository);
        $this->app->instance(IdentityFactoryInterface::class, $identityFactory);
        $this->app->instance(EventDispatcherInterface::class, $eventDispatcher);

        $handler = $this->app->make(DelegationApprovedHandler::class);

        $this->expectException(IdentityNotFoundException::class);
        $this->expectExceptionMessage('Original identity not found.');

        $handler->handle($event);
    }

    private function createIdentity(IdentityIdentifier $identityIdentifier): Identity
    {
        return new Identity(
            $identityIdentifier,
            new UserName('test-user'),
            new Email('test@example.com'),
            Language::JAPANESE,
            null,
            HashedPassword::fromPlain(new PlainPassword('Password1!')),
            new DateTimeImmutable(),
        );
    }

    private function createDelegatedIdentity(
        IdentityIdentifier $identityIdentifier,
        DelegationIdentifier $delegationIdentifier,
        IdentityIdentifier $originalIdentityIdentifier,
    ): Identity {
        return new Identity(
            $identityIdentifier,
            new UserName('delegated-user'),
            new Email('delegated@example.com'),
            Language::JAPANESE,
            null,
            HashedPassword::fromPlain(new PlainPassword('Password1!')),
            null,
            [],
            $delegationIdentifier,
            $originalIdentityIdentifier,
        );
    }
}
