<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Application\UseCase\Command\PublishTalent;

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
use Source\Wiki\Talent\Application\Exception\ExistsApprovedButNotTranslatedTalentException;
use Source\Wiki\Talent\Application\Exception\TalentNotFoundException;
use Source\Wiki\Talent\Domain\Entity\Talent;
use Source\Wiki\Talent\Domain\Exception\ExceedMaxRelevantVideoLinksException;
use Source\Wiki\Talent\Domain\Factory\TalentFactoryInterface;
use Source\Wiki\Talent\Domain\Factory\TalentHistoryFactoryInterface;
use Source\Wiki\Talent\Domain\Factory\TalentSnapshotFactoryInterface;
use Source\Wiki\Talent\Domain\Repository\DraftTalentRepositoryInterface;
use Source\Wiki\Talent\Domain\Repository\TalentHistoryRepositoryInterface;
use Source\Wiki\Talent\Domain\Repository\TalentRepositoryInterface;
use Source\Wiki\Talent\Domain\Repository\TalentSnapshotRepositoryInterface;
use Source\Wiki\Talent\Domain\Service\TalentServiceInterface;

readonly class PublishTalent implements PublishTalentInterface
{
    public function __construct(
        private TalentRepositoryInterface         $talentRepository,
        private DraftTalentRepositoryInterface    $draftTalentRepository,
        private TalentServiceInterface            $talentService,
        private TalentFactoryInterface            $talentFactory,
        private TalentHistoryRepositoryInterface  $talentHistoryRepository,
        private TalentHistoryFactoryInterface     $talentHistoryFactory,
        private TalentSnapshotFactoryInterface    $talentSnapshotFactory,
        private TalentSnapshotRepositoryInterface $talentSnapshotRepository,
        private PrincipalRepositoryInterface      $principalRepository,
        private PolicyEvaluatorInterface          $policyEvaluator,
    ) {
    }

    /**
     * @param PublishTalentInputPort $input
     * @return Talent
     * @throws TalentNotFoundException
     * @throws InvalidStatusException
     * @throws ExistsApprovedButNotTranslatedTalentException
     * @throws ExceedMaxRelevantVideoLinksException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function process(PublishTalentInputPort $input): Talent
    {
        $talent = $this->draftTalentRepository->findById($input->talentIdentifier());

        if ($talent === null) {
            throw new TalentNotFoundException();
        }

        $principal = $this->principalRepository->findById($input->principalIdentifier());
        if ($principal === null) {
            throw new PrincipalNotFoundException();
        }
        $groupIds = array_map(
            static fn ($groupIdentifier) => (string) $groupIdentifier,
            $talent->groupIdentifiers()
        );
        $resource = new Resource(
            type: ResourceType::TALENT,
            agencyId: (string) $talent->agencyIdentifier(),
            groupIds: $groupIds,
            talentIds: [(string) $talent->talentIdentifier()],
        );

        if (! $this->policyEvaluator->evaluate($principal, Action::PUBLISH, $resource)) {
            throw new UnauthorizedException();
        }

        if ($talent->status() !== ApprovalStatus::UnderReview) {
            throw new InvalidStatusException();
        }

        // 同じ翻訳セットの別版で承認済みがあるかチェック
        if ($this->talentService->existsApprovedButNotTranslatedTalent(
            $talent->translationSetIdentifier(),
            $talent->talentIdentifier(),
        )) {
            throw new ExistsApprovedButNotTranslatedTalentException();
        }

        if ($talent->publishedTalentIdentifier()) {
            $publishedTalent = $this->talentRepository->findById($input->publishedTalentIdentifier());
            if ($publishedTalent === null) {
                throw new TalentNotFoundException();
            }

            // スナップショット保存（更新前の状態を保存）
            $snapshot = $this->talentSnapshotFactory->create($publishedTalent);
            $this->talentSnapshotRepository->save($snapshot);

            $publishedTalent->setName($talent->name());
            $publishedTalent->updateVersion();
        } else {
            $publishedTalent = $this->talentFactory->create(
                $talent->translationSetIdentifier(),
                $talent->language(),
                $talent->name(),
            );
        }
        $publishedTalent->setRealName($talent->realName());
        if ($talent->agencyIdentifier()) {
            $publishedTalent->setAgencyIdentifier($talent->agencyIdentifier());
        }
        $publishedTalent->setGroupIdentifiers($talent->groupIdentifiers());
        $publishedTalent->setBirthday($talent->birthday());
        $publishedTalent->setCareer($talent->career());
        $publishedTalent->setRelevantVideoLinks($talent->relevantVideoLinks());

        $this->talentRepository->save($publishedTalent);

        $history = $this->talentHistoryFactory->create(
            actionType: HistoryActionType::Publish,
            editorIdentifier: $input->principalIdentifier(),
            submitterIdentifier: $talent->editorIdentifier(),
            talentIdentifier: $talent->publishedTalentIdentifier(),
            draftTalentIdentifier: $talent->talentIdentifier(),
            fromStatus: $talent->status(),
            toStatus: null,
            fromVersion: null,
            toVersion: null,
            subjectName: $talent->name(),
        );
        $this->talentHistoryRepository->save($history);

        $this->draftTalentRepository->delete($talent);

        return $publishedTalent;
    }
}
