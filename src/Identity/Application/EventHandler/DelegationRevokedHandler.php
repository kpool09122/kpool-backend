<?php

declare(strict_types=1);

namespace Source\Identity\Application\EventHandler;

use Source\Account\Domain\Event\DelegationRevoked;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;

readonly class DelegationRevokedHandler
{
    public function __construct(
        private IdentityRepositoryInterface $identityRepository,
    ) {
    }

    public function handle(DelegationRevoked $event): void
    {
        $this->identityRepository->deleteByDelegation($event->delegationIdentifier());
    }
}
