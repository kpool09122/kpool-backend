<?php

declare(strict_types=1);

namespace Source\Identity\Application\EventHandler;

use Source\Account\Domain\Event\DelegationApproved;
use Source\Identity\Domain\Exception\IdentityNotFoundException;
use Source\Identity\Domain\Factory\IdentityFactoryInterface;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;

readonly class DelegationApprovedHandler
{
    public function __construct(
        private IdentityRepositoryInterface $identityRepository,
        private IdentityFactoryInterface $identityFactory,
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
    }
}
