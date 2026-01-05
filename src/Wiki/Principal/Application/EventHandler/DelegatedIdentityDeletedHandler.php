<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\EventHandler;

use Source\Identity\Domain\Event\DelegatedIdentityDeleted;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;

readonly class DelegatedIdentityDeletedHandler
{
    public function __construct(
        private PrincipalRepositoryInterface $principalRepository,
    ) {
    }

    public function handle(DelegatedIdentityDeleted $event): void
    {
        $this->principalRepository->deleteByDelegation($event->delegationIdentifier());
    }
}
