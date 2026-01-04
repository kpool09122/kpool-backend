<?php

declare(strict_types=1);

namespace Source\Account\Application\UseCase\Command\RevokeDelegation;

use Source\Account\Application\Exception\DelegationNotFoundException;
use Source\Account\Application\Exception\DisallowedDelegationOperationException;
use Source\Account\Domain\Entity\OperationDelegation;
use Source\Account\Domain\Event\DelegationRevoked;
use Source\Account\Domain\Repository\DelegationRepositoryInterface;
use Source\Shared\Application\Service\Event\EventDispatcherInterface;

readonly class RevokeDelegation implements RevokeDelegationInterface
{
    public function __construct(
        private DelegationRepositoryInterface $delegationRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function process(RevokeDelegationInputPort $input): OperationDelegation
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
