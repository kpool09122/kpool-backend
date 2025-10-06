<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Application\UseCase\Command\SubmitAgency;

use Source\Wiki\Agency\Application\Exception\AgencyNotFoundException;
use Source\Wiki\Agency\Domain\Entity\DraftAgency;
use Source\Wiki\Agency\Domain\Repository\AgencyRepositoryInterface;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

readonly class SubmitAgency implements SubmitAgencyInterface
{
    public function __construct(
        private AgencyRepositoryInterface $agencyRepository,
    ) {
    }

    /**
     * @param SubmitAgencyInputPort $input
     * @return DraftAgency
     * @throws AgencyNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     */
    public function process(SubmitAgencyInputPort $input): DraftAgency
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

        if (! $principal->role()->can(Action::SUBMIT, $resourceIdentifier, $principal)) {
            throw new UnauthorizedException();
        }

        if ($agency->status() !== ApprovalStatus::Pending
        && $agency->status() !== ApprovalStatus::Rejected) {
            throw new InvalidStatusException();
        }

        $agency->setStatus(ApprovalStatus::UnderReview);

        $this->agencyRepository->saveDraft($agency);

        return $agency;
    }
}
