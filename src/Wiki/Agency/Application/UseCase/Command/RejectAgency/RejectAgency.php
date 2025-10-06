<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Application\UseCase\Command\RejectAgency;

use Source\Wiki\Agency\Application\Exception\AgencyNotFoundException;
use Source\Wiki\Agency\Domain\Entity\DraftAgency;
use Source\Wiki\Agency\Domain\Repository\AgencyRepositoryInterface;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

class RejectAgency implements RejectAgencyInterface
{
    public function __construct(
        private AgencyRepositoryInterface $agencyRepository,
    ) {
    }

    /**
     * @param RejectAgencyInputPort $input
     * @return DraftAgency
     * @throws AgencyNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     */
    public function process(RejectAgencyInputPort $input): DraftAgency
    {
        $agency = $this->agencyRepository->findDraftById($input->agencyIdentifier());

        if ($agency === null) {
            throw new AgencyNotFoundException();
        }

        $principal = $input->principal();
        $resourceIdentifier = new ResourceIdentifier(
            type: ResourceType::AGENCY,
            agencyId: (string) $agency->agencyIdentifier(),
            groupIds: [],
        );

        if (! $principal->role()->can(Action::REJECT, $resourceIdentifier, $principal)) {
            throw new UnauthorizedException();
        }

        if ($agency->status() !== ApprovalStatus::UnderReview) {
            throw new InvalidStatusException();
        }

        $agency->setStatus(ApprovalStatus::Rejected);

        $this->agencyRepository->saveDraft($agency);

        return $agency;
    }
}
