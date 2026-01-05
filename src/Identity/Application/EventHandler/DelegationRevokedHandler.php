<?php

declare(strict_types=1);

namespace Source\Identity\Application\EventHandler;

use DateTimeImmutable;
use Source\Account\Domain\Event\DelegationRevoked;
use Source\Identity\Domain\Event\DelegatedIdentityDeleted;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;
use Source\Shared\Application\Service\Event\EventDispatcherInterface;

readonly class DelegationRevokedHandler
{
    public function __construct(
        private IdentityRepositoryInterface $identityRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function handle(DelegationRevoked $event): void
    {
        $this->identityRepository->deleteByDelegation($event->delegationIdentifier());

        $this->eventDispatcher->dispatch(new DelegatedIdentityDeleted(
            $event->delegationIdentifier(),
            new DateTimeImmutable(),
        ));
    }
}
