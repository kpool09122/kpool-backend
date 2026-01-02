<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Application\UseCase\Command\RejectAgency;

use Source\Wiki\Agency\Application\Exception\AgencyNotFoundException;
use Source\Wiki\Agency\Domain\Entity\DraftAgency;
use Source\Wiki\Agency\Domain\Factory\AgencyHistoryFactoryInterface;
use Source\Wiki\Agency\Domain\Repository\AgencyHistoryRepositoryInterface;
use Source\Wiki\Agency\Domain\Repository\DraftAgencyRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

readonly class RejectAgency implements RejectAgencyInterface
{
    public function __construct(
        private DraftAgencyRepositoryInterface   $agencyRepository,
        private AgencyHistoryRepositoryInterface $agencyHistoryRepository,
        private AgencyHistoryFactoryInterface    $agencyHistoryFactory,
        private PrincipalRepositoryInterface     $principalRepository,
        private PolicyEvaluatorInterface         $policyEvaluator,
    ) {
    }

    /**
     * @param RejectAgencyInputPort $input
     * @return DraftAgency
     * @throws AgencyNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function process(RejectAgencyInputPort $input): DraftAgency
    {
        $agency = $this->agencyRepository->findById($input->agencyIdentifier());

        if ($agency === null) {
            throw new AgencyNotFoundException();
        }

        $principal = $this->principalRepository->findById($input->principalIdentifier());
        if ($principal === null) {
            throw new PrincipalNotFoundException();
        }

        $resourceIdentifier = new ResourceIdentifier(
            type: ResourceType::AGENCY,
            agencyId: (string) $agency->agencyIdentifier(),
            groupIds: [],
        );

        if (! $this->policyEvaluator->evaluate($principal, Action::REJECT, $resourceIdentifier)) {
            throw new UnauthorizedException();
        }

        if ($agency->status() !== ApprovalStatus::UnderReview) {
            throw new InvalidStatusException();
        }

        $previousStatus = $agency->status();
        $agency->setStatus(ApprovalStatus::Rejected);

        $this->agencyRepository->save($agency);

        $history = $this->agencyHistoryFactory->create(
            $input->principalIdentifier(),
            $agency->editorIdentifier(),
            $agency->publishedAgencyIdentifier(),
            $agency->agencyIdentifier(),
            $previousStatus,
            $agency->status(),
            $agency->name(),
        );
        $this->agencyHistoryRepository->save($history);

        return $agency;
    }
}
