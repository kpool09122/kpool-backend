<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Application\UseCase\Command\PublishAgency;

use Source\Wiki\Agency\Application\Exception\AgencyNotFoundException;
use Source\Wiki\Agency\Application\Exception\ExistsApprovedButNotTranslatedAgencyException;
use Source\Wiki\Agency\Domain\Entity\Agency;
use Source\Wiki\Agency\Domain\Factory\AgencyFactoryInterface;
use Source\Wiki\Agency\Domain\Factory\AgencyHistoryFactoryInterface;
use Source\Wiki\Agency\Domain\Factory\AgencySnapshotFactoryInterface;
use Source\Wiki\Agency\Domain\Repository\AgencyHistoryRepositoryInterface;
use Source\Wiki\Agency\Domain\Repository\AgencyRepositoryInterface;
use Source\Wiki\Agency\Domain\Repository\AgencySnapshotRepositoryInterface;
use Source\Wiki\Agency\Domain\Repository\DraftAgencyRepositoryInterface;
use Source\Wiki\Agency\Domain\Service\AgencyServiceInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\HistoryActionType;
use Source\Wiki\Shared\Domain\ValueObject\Resource;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

readonly class PublishAgency implements PublishAgencyInterface
{
    public function __construct(
        private AgencyRepositoryInterface         $agencyRepository,
        private DraftAgencyRepositoryInterface    $draftAgencyRepository,
        private AgencyServiceInterface            $agencyService,
        private AgencyFactoryInterface            $agencyFactory,
        private AgencyHistoryRepositoryInterface  $agencyHistoryRepository,
        private AgencyHistoryFactoryInterface     $agencyHistoryFactory,
        private AgencySnapshotFactoryInterface    $agencySnapshotFactory,
        private AgencySnapshotRepositoryInterface $agencySnapshotRepository,
        private PrincipalRepositoryInterface      $principalRepository,
        private PolicyEvaluatorInterface          $policyEvaluator,
    ) {
    }

    /**
     * @param PublishAgencyInputPort $input
     * @return Agency
     * @throws AgencyNotFoundException
     * @throws InvalidStatusException
     * @throws ExistsApprovedButNotTranslatedAgencyException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function process(PublishAgencyInputPort $input): Agency
    {
        $agency = $this->draftAgencyRepository->findById($input->agencyIdentifier());

        if ($agency === null) {
            throw new AgencyNotFoundException();
        }

        if ($agency->status() !== ApprovalStatus::UnderReview) {
            throw new InvalidStatusException();
        }

        $principal = $this->principalRepository->findById($input->principalIdentifier());
        if ($principal === null) {
            throw new PrincipalNotFoundException();
        }
        $resource = new Resource(
            type: ResourceType::AGENCY,
            agencyId: (string) $agency->agencyIdentifier(),
            groupIds: [],
        );

        if (! $this->policyEvaluator->evaluate($principal, Action::PUBLISH, $resource)) {
            throw new UnauthorizedException();
        }

        // 同じ翻訳セットの別版で承認済みがあるかチェック
        if ($this->agencyService->existsApprovedButNotTranslatedAgency(
            $agency->translationSetIdentifier(),
            $agency->agencyIdentifier(),
        )) {
            throw new ExistsApprovedButNotTranslatedAgencyException();
        }

        if ($agency->publishedAgencyIdentifier()) {
            $publishedAgency = $this->agencyRepository->findById($input->publishedAgencyIdentifier());
            if ($publishedAgency === null) {
                throw new AgencyNotFoundException();
            }

            // スナップショット保存（更新前の状態を保存）
            $snapshot = $this->agencySnapshotFactory->create($publishedAgency);
            $this->agencySnapshotRepository->save($snapshot);

            $publishedAgency->setName($agency->name());
            $publishedAgency->setNormalizedName($agency->normalizedName());
            $publishedAgency->updateVersion();
        } else {
            $publishedAgency = $this->agencyFactory->create(
                $agency->translationSetIdentifier(),
                $agency->language(),
                $agency->name(),
            );
        }
        $publishedAgency->setCEO($agency->CEO());
        $publishedAgency->setNormalizedCEO($agency->normalizedCEO());
        $publishedAgency->setDescription($agency->description());
        $publishedAgency->setFoundedIn($agency->foundedIn());

        $this->agencyRepository->save($publishedAgency);

        $history = $this->agencyHistoryFactory->create(
            actionType: HistoryActionType::Publish,
            editorIdentifier: $input->principalIdentifier(),
            submitterIdentifier: $agency->editorIdentifier(),
            agencyIdentifier: $agency->publishedAgencyIdentifier(),
            draftAgencyIdentifier: $agency->agencyIdentifier(),
            fromStatus: $agency->status(),
            toStatus: null,
            fromVersion: null,
            toVersion: null,
            subjectName: $agency->name(),
        );
        $this->agencyHistoryRepository->save($history);

        $this->draftAgencyRepository->delete($agency);

        return $publishedAgency;
    }
}
