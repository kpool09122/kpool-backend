<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Application\UseCase\Command\ApproveAgency;

use Source\Wiki\Agency\Application\Exception\AgencyNotFoundException;
use Source\Wiki\Agency\Application\Exception\ExistsApprovedButNotTranslatedAgencyException;
use Source\Wiki\Agency\Domain\Entity\DraftAgency;
use Source\Wiki\Agency\Domain\Factory\AgencyHistoryFactoryInterface;
use Source\Wiki\Agency\Domain\Repository\AgencyHistoryRepositoryInterface;
use Source\Wiki\Agency\Domain\Repository\DraftAgencyRepositoryInterface;
use Source\Wiki\Agency\Domain\Service\AgencyServiceInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

readonly class ApproveAgency implements ApproveAgencyInterface
{
    public function __construct(
        private DraftAgencyRepositoryInterface   $agencyRepository,
        private AgencyServiceInterface           $agencyService,
        private AgencyHistoryRepositoryInterface $agencyHistoryRepository,
        private AgencyHistoryFactoryInterface    $agencyHistoryFactory,
        private PrincipalRepositoryInterface     $principalRepository,
    ) {
    }

    /**
     * @param ApproveAgencyInputPort $input
     * @return DraftAgency
     * @throws AgencyNotFoundException
     * @throws ExistsApprovedButNotTranslatedAgencyException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function process(ApproveAgencyInputPort $input): DraftAgency
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

        if (! $principal->role()->can(Action::APPROVE, $resourceIdentifier, $principal)) {
            throw new UnauthorizedException();
        }

        if ($agency->status() !== ApprovalStatus::UnderReview) {
            throw new InvalidStatusException();
        }

        // 同じ翻訳セットの別版で承認済みがあるかチェック
        if ($this->agencyService->existsApprovedButNotTranslatedAgency(
            $agency->translationSetIdentifier(),
            $agency->agencyIdentifier(),
        )) {
            throw new ExistsApprovedButNotTranslatedAgencyException();
        }

        $previousStatus = $agency->status();
        $agency->setStatus(ApprovalStatus::Approved);

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
