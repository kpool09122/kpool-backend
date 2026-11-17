<?php

declare(strict_types=1);

namespace Tests\Identity\Application\Service;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Identity\Application\Service\AuthorizationRevocationService;
use Source\Identity\Application\Service\AuthorizationTokenLifecycleServiceInterface;
use Source\Identity\Domain\Entity\AuthorizationConnection;
use Source\Identity\Domain\Event\AuthorizationConnectionRevoked;
use Source\Identity\Domain\Repository\AuthorizationConnectionRepositoryInterface;
use Source\Identity\Domain\ValueObject\AccountPrincipalIdentifier;
use Source\Identity\Domain\ValueObject\AuthorizationConnectionIdentifier;
use Source\Identity\Domain\ValueObject\AuthorizationScope;
use Source\Identity\Domain\ValueObject\OAuthClientIdentifier;
use Source\Shared\Application\Service\Event\EventDispatcherInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;

class AuthorizationRevocationServiceTest extends TestCase
{
    public function testRevokesTheConnectionBeforeDeletingAuthorizationDataForTheIdentity(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $connectionIdentifier = new AuthorizationConnectionIdentifier(StrTestHelper::generateUuid());
        $connection = new AuthorizationConnection(
            $connectionIdentifier,
            $identityIdentifier,
            new OAuthClientIdentifier(StrTestHelper::generateUuid()),
            [AuthorizationScope::WikiRead],
            new AccountIdentifier(StrTestHelper::generateUuid()),
            new AccountPrincipalIdentifier(StrTestHelper::generateUuid()),
            'https://mcp.example.test/mcp',
            new DateTimeImmutable(),
        );
        $authorizationConnectionRepository = $this->createMock(AuthorizationConnectionRepositoryInterface::class);
        $authorizationConnectionRepository->expects($this->once())->method('findByIdentityIdentifier')
            ->with($identityIdentifier)->willReturn([$connection]);
        $authorizationConnectionRepository->expects($this->once())->method('save')
            ->with($this->callback(static fn (AuthorizationConnection $saved): bool => $saved === $connection && $saved->revokedAt() !== null));

        $authorizationTokenLifecycleService = $this->createMock(AuthorizationTokenLifecycleServiceInterface::class);
        $authorizationTokenLifecycleService->expects($this->once())->method('revoke')->with($connectionIdentifier);
        $authorizationTokenLifecycleService->expects($this->once())->method('deleteForIdentity')
            ->with($identityIdentifier)->willReturnCallback(static function () use ($connection): void {
                self::assertNotNull($connection->revokedAt());
            });

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects($this->once())->method('dispatch')
            ->with(new AuthorizationConnectionRevoked($identityIdentifier, $connectionIdentifier));

        $authorizationRevocationService = new AuthorizationRevocationService(
            $authorizationConnectionRepository,
            $authorizationTokenLifecycleService,
            $eventDispatcher,
        );
        $authorizationRevocationService->revokeAndDeleteForIdentity($identityIdentifier);
    }
}
