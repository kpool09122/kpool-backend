<?php

declare(strict_types=1);

namespace Tests\Identity\Application\EventHandler;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Account\Domain\Event\DelegationRevoked;
use Source\Identity\Application\EventHandler\DelegationRevokedHandler;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class DelegationRevokedHandlerTest extends TestCase
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

        $this->app->instance(IdentityRepositoryInterface::class, $identityRepository);

        $handler = $this->app->make(DelegationRevokedHandler::class);

        $this->assertInstanceOf(DelegationRevokedHandler::class, $handler);
    }

    /**
     * 正常系: 委譲が取り消された時に委譲Identityが削除されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testHandleDeletesDelegatedIdentity(): void
    {
        $delegationId = new DelegationIdentifier(StrTestHelper::generateUuid());
        $revokedAt = new DateTimeImmutable();

        $event = new DelegationRevoked($delegationId, $revokedAt);

        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        $identityRepository->shouldReceive('deleteByDelegation')
            ->once()
            ->with($delegationId)
            ->andReturnNull();

        $this->app->instance(IdentityRepositoryInterface::class, $identityRepository);

        $handler = $this->app->make(DelegationRevokedHandler::class);

        $handler->handle($event);
    }
}
