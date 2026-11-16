<?php

declare(strict_types=1);

namespace Source\Identity\Application\Service;

use DateTimeImmutable;
use Source\Identity\Domain\Entity\AuthorizationConsent;
use Source\Identity\Domain\Event\AuthorizationConsentRevoked;
use Source\Identity\Domain\Repository\AuthorizationConsentRepositoryInterface;
use Source\Shared\Application\Service\Event\EventDispatcherInterface;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class AuthorizationRevocationService implements AuthorizationRevocationServiceInterface
{
    public function __construct(
        private AuthorizationConsentRepositoryInterface $authorizationConsentRepository,
        private AuthorizationTokenLifecycleServiceInterface $authorizationTokenLifecycleService,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function revoke(AuthorizationConsent $consent): void
    {
        $consent->revoke(new DateTimeImmutable());
        $this->authorizationConsentRepository->save($consent);
        $this->authorizationTokenLifecycleService->revoke($consent->identifier());
        $this->eventDispatcher->dispatch(new AuthorizationConsentRevoked($consent->identityIdentifier(), $consent->identifier()));
    }

    public function revokeAndDeleteForIdentity(IdentityIdentifier $identityIdentifier): void
    {
        foreach ($this->authorizationConsentRepository->findByIdentityIdentifier($identityIdentifier) as $consent) {
            $this->revoke($consent);
        }
        $this->authorizationTokenLifecycleService->deleteForIdentity($identityIdentifier);
    }
}
