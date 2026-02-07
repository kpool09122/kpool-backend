<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Command\PublishWiki;

use Source\Wiki\Principal\Application\Service\ContributionPointServiceInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\HistoryActionType;
use Source\Wiki\Shared\Domain\ValueObject\Resource;
use Source\Wiki\Wiki\Application\Exception\InconsistentVersionException;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Source\Wiki\Wiki\Domain\Entity\Wiki;
use Source\Wiki\Wiki\Domain\Factory\WikiFactoryInterface;
use Source\Wiki\Wiki\Domain\Factory\WikiHistoryFactoryInterface;
use Source\Wiki\Wiki\Domain\Factory\WikiSnapshotFactoryInterface;
use Source\Wiki\Wiki\Domain\Repository\DraftWikiRepositoryInterface;
use Source\Wiki\Wiki\Domain\Repository\WikiHistoryRepositoryInterface;
use Source\Wiki\Wiki\Domain\Repository\WikiRepositoryInterface;
use Source\Wiki\Wiki\Domain\Repository\WikiSnapshotRepositoryInterface;
use Source\Wiki\Wiki\Domain\Service\WikiServiceInterface;
use Source\Wiki\Wiki\Domain\ValueObject\DraftWikiIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

readonly class PublishWiki implements PublishWikiInterface
{
    public function __construct(
        private WikiRepositoryInterface           $wikiRepository,
        private DraftWikiRepositoryInterface      $draftWikiRepository,
        private WikiServiceInterface              $wikiService,
        private WikiFactoryInterface              $wikiFactory,
        private WikiHistoryRepositoryInterface    $wikiHistoryRepository,
        private WikiHistoryFactoryInterface       $wikiHistoryFactory,
        private WikiSnapshotFactoryInterface      $wikiSnapshotFactory,
        private WikiSnapshotRepositoryInterface   $wikiSnapshotRepository,
        private PrincipalRepositoryInterface      $principalRepository,
        private PolicyEvaluatorInterface          $policyEvaluator,
        private ContributionPointServiceInterface $contributionPointService,
    ) {
    }

    /**
     * @param PublishWikiInputPort $input
     * @return Wiki
     * @throws WikiNotFoundException
     * @throws InvalidStatusException
     * @throws InconsistentVersionException
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function process(PublishWikiInputPort $input): Wiki
    {
        $wiki = $this->draftWikiRepository->findById($input->wikiIdentifier());

        if ($wiki === null) {
            throw new WikiNotFoundException();
        }

        if ($wiki->status() !== ApprovalStatus::UnderReview) {
            throw new InvalidStatusException();
        }

        $principal = $this->principalRepository->findById($input->principalIdentifier());
        if ($principal === null) {
            throw new PrincipalNotFoundException();
        }

        $resource = new Resource(
            type: $input->resourceType(),
            agencyId: $input->agencyIdentifier() ? (string) $input->agencyIdentifier() : null,
            groupIds: array_map(
                static fn (WikiIdentifier $id) => (string) $id,
                $input->groupIdentifiers(),
            ),
            talentIds: array_map(
                static fn (WikiIdentifier $id) => (string) $id,
                $input->talentIdentifiers(),
            ),
        );

        if (! $this->policyEvaluator->evaluate($principal, Action::PUBLISH, $resource)) {
            throw new DisallowedException();
        }

        // 同じ翻訳セットの公開Wikiのバージョンが揃っているかチェック
        if (! $this->wikiService->hasConsistentVersions(
            $wiki->translationSetIdentifier(),
        )) {
            throw new InconsistentVersionException();
        }

        if ($wiki->publishedWikiIdentifier()) {
            $publishedWiki = $this->wikiRepository->findById($input->publishedWikiIdentifier());
            if ($publishedWiki === null) {
                throw new WikiNotFoundException();
            }

            // スナップショット保存（更新前の状態を保存）
            $snapshot = $this->wikiSnapshotFactory->create($publishedWiki);
            $this->wikiSnapshotRepository->save($snapshot);

            $publishedWiki->setBasic($wiki->basic());
            $publishedWiki->updateVersion();
        } else {
            $publishedWiki = $this->wikiFactory->create(
                $wiki->translationSetIdentifier(),
                $wiki->slug(),
                $wiki->language(),
                $wiki->resourceType(),
                $wiki->basic(),
            );
        }
        $publishedWiki->setSections($wiki->sections());
        $publishedWiki->setThemeColor($wiki->themeColor());
        $publishedWiki->setEditorIdentifier($wiki->editorIdentifier());
        $publishedWiki->setApproverIdentifier($wiki->approverIdentifier());
        $publishedWiki->setMergerIdentifier($wiki->mergerIdentifier());
        $publishedWiki->setMergedAt($wiki->mergedAt());
        $publishedWiki->setSourceEditorIdentifier($wiki->sourceEditorIdentifier());
        $publishedWiki->setTranslatedAt($wiki->translatedAt());
        $publishedWiki->setApprovedAt($wiki->approvedAt());

        $this->wikiRepository->save($publishedWiki);

        $history = $this->wikiHistoryFactory->create(
            actionType: HistoryActionType::Publish,
            actorIdentifier: $input->principalIdentifier(),
            submitterIdentifier: $wiki->editorIdentifier(),
            wikiIdentifier: $wiki->publishedWikiIdentifier(),
            draftWikiIdentifier: new DraftWikiIdentifier((string) $wiki->wikiIdentifier()),
            fromStatus: $wiki->status(),
            toStatus: null,
            fromVersion: null,
            toVersion: null,
            subjectName: $wiki->basic()->name(),
        );
        $this->wikiHistoryRepository->save($history);

        // Grant contribution points
        $isNewCreation = $wiki->publishedWikiIdentifier() === null;
        if ($wiki->approverIdentifier() !== null) {
            $this->contributionPointService->grantPoints(
                editorIdentifier: $wiki->editorIdentifier(),
                approverIdentifier: $wiki->approverIdentifier(),
                mergerIdentifier: $wiki->mergerIdentifier(),
                resourceType: $wiki->resourceType(),
                resourceId: (string) $publishedWiki->wikiIdentifier(),
                isNewCreation: $isNewCreation,
            );
        }

        $this->draftWikiRepository->delete($wiki);

        return $publishedWiki;
    }
}
