<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\EventHandler;

use Source\Identity\Domain\Event\DelegatedIdentityCreated;
use Source\Wiki\Principal\Domain\Factory\PrincipalFactoryInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;

readonly class DelegatedIdentityCreatedHandler
{
    public function __construct(
        private PrincipalRepositoryInterface $principalRepository,
        private PrincipalFactoryInterface $principalFactory,
    ) {
    }

    public function handle(DelegatedIdentityCreated $event): void
    {
        $originalPrincipal = $this->principalRepository->findByIdentityIdentifier(
            $event->originalIdentityIdentifier()
        );

        if ($originalPrincipal === null) {
            return;
        }

        $delegatedPrincipal = $this->principalFactory->createDelegatedPrincipal(
            $originalPrincipal,
            $event->delegationIdentifier(),
            $event->delegatedIdentityIdentifier(),
        );

        $this->principalRepository->save($delegatedPrincipal);
    }
}
