<?php

declare(strict_types=1);

namespace Source\Account\Delegation\Application\UseCase\Command\ApproveDelegation;

use Source\Account\Delegation\Application\Exception\DelegationNotFoundException;
use Source\Account\Delegation\Application\Exception\DisallowedDelegationOperationException;
use Source\Account\Delegation\Domain\Entity\Delegation;
use Source\Account\Delegation\Domain\Event\DelegationApproved;
use Source\Account\Delegation\Domain\Repository\DelegationRepositoryInterface;
use Source\Shared\Application\Service\Event\EventDispatcherInterface;

readonly class ApproveDelegation implements ApproveDelegationInterface
{
    public function __construct(
        private DelegationRepositoryInterface $delegationRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function process(ApproveDelegationInputPort $input): Delegation
    {
        $delegation = $this->delegationRepository->findById($input->delegationIdentifier());

        if ($delegation === null) {
            throw new DelegationNotFoundException('Delegation not found.');
        }

        if ((string) $delegation->delegatorIdentifier() !== (string) $input->approverIdentifier()) {
            throw new DisallowedDelegationOperationException('Only the delegator can approve this delegation.');
        }

        $delegation->approve();

        $this->delegationRepository->save($delegation);

        $this->eventDispatcher->dispatch(new DelegationApproved(
            $delegation->delegationIdentifier(),
            $delegation->delegateIdentifier(),
            $delegation->delegatorIdentifier(),
            $delegation->approvedAt(),
        ));

        return $delegation;
    }
}
