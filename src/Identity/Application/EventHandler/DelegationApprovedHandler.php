<?php

declare(strict_types=1);

namespace Source\Identity\Application\EventHandler;

use DateTimeImmutable;
use Source\Account\Domain\Event\DelegationApproved;
use Source\Identity\Domain\Event\DelegatedIdentityCreated;
use Source\Identity\Domain\Exception\IdentityNotFoundException;
use Source\Identity\Domain\Factory\IdentityFactoryInterface;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;
use Source\Shared\Application\Service\Event\EventDispatcherInterface;

readonly class DelegationApprovedHandler
{
    public function __construct(
        private IdentityRepositoryInterface $identityRepository,
        private IdentityFactoryInterface $identityFactory,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * @throws IdentityNotFoundException
     */
    public function handle(DelegationApproved $event): void
    {
        $originalIdentity = $this->identityRepository->findById($event->delegatorIdentifier());

        if ($originalIdentity === null) {
            throw new IdentityNotFoundException('Original identity not found.');
        }

        $delegatedIdentity = $this->identityFactory->createDelegatedIdentity(
            $originalIdentity,
            $event->delegationIdentifier(),
        );

        $this->identityRepository->save($delegatedIdentity);

        $this->eventDispatcher->dispatch(new DelegatedIdentityCreated(
            $event->delegationIdentifier(),
            $delegatedIdentity->identityIdentifier(),
            $originalIdentity->identityIdentifier(),
            new DateTimeImmutable(),
        ));
    }
}
