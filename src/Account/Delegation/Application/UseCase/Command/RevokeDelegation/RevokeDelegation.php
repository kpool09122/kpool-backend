<?php

declare(strict_types=1);

namespace Source\Account\Delegation\Application\UseCase\Command\RevokeDelegation;

use Source\Account\Delegation\Application\Exception\DelegationNotFoundException;
use Source\Account\Delegation\Application\Exception\DisallowedDelegationOperationException;
use Source\Account\Delegation\Domain\Entity\Delegation;
use Source\Account\Delegation\Domain\Event\DelegationRevoked;
use Source\Account\Delegation\Domain\Repository\DelegationRepositoryInterface;
use Source\Shared\Application\Service\Event\EventDispatcherInterface;

readonly class RevokeDelegation implements RevokeDelegationInterface
{
    public function __construct(
        private DelegationRepositoryInterface $delegationRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function process(RevokeDelegationInputPort $input): Delegation
    {
        $delegation = $this->delegationRepository->findById($input->delegationIdentifier());

        if ($delegation === null) {
            throw new DelegationNotFoundException('Delegation not found.');
        }

        $revokerId = (string) $input->revokerIdentifier();
        $delegatorId = (string) $delegation->delegatorIdentifier();
        $delegateId = (string) $delegation->delegateIdentifier();

        if ($revokerId !== $delegatorId && $revokerId !== $delegateId) {
            throw new DisallowedDelegationOperationException('Only the delegator or delegate can revoke this delegation.');
        }

        $delegation->revoke();

        $this->delegationRepository->save($delegation);

        $this->eventDispatcher->dispatch(new DelegationRevoked(
            $delegation->delegationIdentifier(),
            $delegation->revokedAt(),
        ));

        return $delegation;
    }
}
