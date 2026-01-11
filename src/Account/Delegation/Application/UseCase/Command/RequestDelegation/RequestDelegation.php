<?php

declare(strict_types=1);

namespace Source\Account\Delegation\Application\UseCase\Command\RequestDelegation;

use Source\Account\Affiliation\Application\Exception\AffiliationNotFoundException;
use Source\Account\Affiliation\Application\Exception\InvalidAffiliationStatusException;
use Source\Account\Affiliation\Domain\Repository\AffiliationRepositoryInterface;
use Source\Account\Delegation\Domain\Entity\Delegation;
use Source\Account\Delegation\Domain\Factory\DelegationFactoryInterface;
use Source\Account\Delegation\Domain\Repository\DelegationRepositoryInterface;

readonly class RequestDelegation implements RequestDelegationInterface
{
    public function __construct(
        private AffiliationRepositoryInterface $affiliationRepository,
        private DelegationRepositoryInterface $delegationRepository,
        private DelegationFactoryInterface $delegationFactory,
    ) {
    }

    public function process(RequestDelegationInputPort $input): Delegation
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
