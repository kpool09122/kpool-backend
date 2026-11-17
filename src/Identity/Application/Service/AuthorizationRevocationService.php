<?php

declare(strict_types=1);

namespace Source\Identity\Application\Service;

use DateTimeImmutable;
use Source\Identity\Domain\Entity\AuthorizationConnection;
use Source\Identity\Domain\Event\AuthorizationConnectionRevoked;
use Source\Identity\Domain\Repository\AuthorizationConnectionRepositoryInterface;
use Source\Shared\Application\Service\Event\EventDispatcherInterface;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class AuthorizationRevocationService implements AuthorizationRevocationServiceInterface
{
    public function __construct(
        private AuthorizationConnectionRepositoryInterface $authorizationConnectionRepository,
        private AuthorizationTokenLifecycleServiceInterface $authorizationTokenLifecycleService,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function revoke(AuthorizationConnection $connection): void
    {
        $connection->revoke(new DateTimeImmutable());
        $this->authorizationConnectionRepository->save($connection);
        $this->authorizationTokenLifecycleService->revoke($connection->identifier());
        $this->eventDispatcher->dispatch(new AuthorizationConnectionRevoked($connection->identityIdentifier(), $connection->identifier()));
    }

    public function revokeAndDeleteForIdentity(IdentityIdentifier $identityIdentifier): void
    {
        foreach ($this->authorizationConnectionRepository->findByIdentityIdentifier($identityIdentifier) as $connection) {
            $this->revoke($connection);
        }
        $this->authorizationTokenLifecycleService->deleteForIdentity($identityIdentifier);
    }
}
