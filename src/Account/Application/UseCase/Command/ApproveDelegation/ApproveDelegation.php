<?php

declare(strict_types=1);

namespace Source\Account\Application\UseCase\Command\ApproveDelegation;

use Source\Account\Application\Exception\DelegationNotFoundException;
use Source\Account\Application\Exception\DisallowedDelegationOperationException;
use Source\Account\Domain\Entity\OperationDelegation;
use Source\Account\Domain\Event\DelegationApproved;
use Source\Account\Domain\Repository\DelegationRepositoryInterface;
use Source\Shared\Application\Service\Event\EventDispatcherInterface;

readonly class ApproveDelegation implements ApproveDelegationInterface
{
    public function __construct(
        private DelegationRepositoryInterface $delegationRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function process(ApproveDelegationInputPort $input): OperationDelegation
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
