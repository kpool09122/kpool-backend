<?php

declare(strict_types=1);

namespace Source\Account\Application\UseCase\Command\RequestDelegation;

use Source\Account\Application\Exception\AffiliationNotFoundException;
use Source\Account\Application\Exception\InvalidAffiliationStatusException;
use Source\Account\Domain\Entity\OperationDelegation;
use Source\Account\Domain\Factory\DelegationFactoryInterface;
use Source\Account\Domain\Repository\AffiliationRepositoryInterface;
use Source\Account\Domain\Repository\DelegationRepositoryInterface;

readonly class RequestDelegation implements RequestDelegationInterface
{
    public function __construct(
        private AffiliationRepositoryInterface $affiliationRepository,
        private DelegationRepositoryInterface $delegationRepository,
        private DelegationFactoryInterface $delegationFactory,
    ) {
    }

    public function process(RequestDelegationInputPort $input): OperationDelegation
    {
        $affiliation = $this->affiliationRepository->findById($input->affiliationIdentifier());

        if ($affiliation === null) {
            throw new AffiliationNotFoundException('Affiliation not found.');
        }

        if (! $affiliation->isActive()) {
            throw new InvalidAffiliationStatusException('Delegation can only be requested for active affiliations.');
        }

        $delegation = $this->delegationFactory->create(
            $input->affiliationIdentifier(),
            $input->delegateIdentifier(),
            $input->delegatorIdentifier(),
        );

        $this->delegationRepository->save($delegation);

        return $delegation;
    }
}
