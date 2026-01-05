<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Application\EventHandler;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Identity\Domain\Event\DelegatedIdentityDeleted;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Wiki\Principal\Application\EventHandler\DelegatedIdentityDeletedHandler;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class DelegatedIdentityDeletedHandlerTest extends TestCase
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

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);

        $handler = $this->app->make(DelegatedIdentityDeletedHandler::class);

        $this->assertInstanceOf(DelegatedIdentityDeletedHandler::class, $handler);
    }

    /**
     * 正常系: 委譲Identityが削除された時に委譲Principalが削除されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testHandleDeletesDelegatedPrincipal(): void
    {
        $delegationId = new DelegationIdentifier(StrTestHelper::generateUuid());
        $deletedAt = new DateTimeImmutable();

        $event = new DelegatedIdentityDeleted(
            $delegationId,
            $deletedAt,
        );

        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('deleteByDelegation')
            ->once()
            ->with($delegationId);

        $this->app->instance(PrincipalRepositoryInterface::class, $principalRepository);

        $handler = $this->app->make(DelegatedIdentityDeletedHandler::class);

        $handler->handle($event);
    }
}
