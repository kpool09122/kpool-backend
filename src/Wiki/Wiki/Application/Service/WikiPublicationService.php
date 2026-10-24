<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\Service;

use DateTimeImmutable;
use Source\Wiki\Principal\Application\Service\ContributionPointServiceInterface;
use Source\Wiki\Shared\Domain\ValueObject\HistoryActionType;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Shared\Domain\ValueObject\VisitorLocation;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Source\Wiki\Wiki\Domain\Entity\DraftWiki;
use Source\Wiki\Wiki\Domain\Entity\Wiki;
use Source\Wiki\Wiki\Domain\Factory\WikiFactoryInterface;
use Source\Wiki\Wiki\Domain\Factory\WikiHistoryFactoryInterface;
use Source\Wiki\Wiki\Domain\Factory\WikiSnapshotFactoryInterface;
use Source\Wiki\Wiki\Domain\Repository\DraftWikiRepositoryInterface;
use Source\Wiki\Wiki\Domain\Repository\WikiHistoryRepositoryInterface;
use Source\Wiki\Wiki\Domain\Repository\WikiRepositoryInterface;
use Source\Wiki\Wiki\Domain\Repository\WikiSnapshotRepositoryInterface;
use Source\Wiki\Wiki\Domain\ValueObject\DraftWikiIdentifier;

readonly class WikiPublicationService implements WikiPublicationServiceInterface
{
    public function __construct(
        private WikiRepositoryInterface           $wikiRepository,
        private DraftWikiRepositoryInterface      $draftWikiRepository,
        private WikiFactoryInterface              $wikiFactory,
        private WikiHistoryRepositoryInterface    $wikiHistoryRepository,
        private WikiHistoryFactoryInterface       $wikiHistoryFactory,
        private WikiSnapshotFactoryInterface      $wikiSnapshotFactory,
        private WikiSnapshotRepositoryInterface   $wikiSnapshotRepository,
        private ContributionPointServiceInterface $contributionPointService,
        private WikiWorkflowNotificationServiceInterface $wikiWorkflowNotificationService,
        private VideoLinkCollectionRegistrationServiceInterface $videoLinkCollectionRegistrationService,
    ) {
    }

    /** @throws WikiNotFoundException */
    public function publish(DraftWiki $wiki, Version $publishVersion, PrincipalIdentifier $principalIdentifier, VisitorLocation $visitorLocation, ?Wiki $publishedWiki = null): Wiki
    {
        if ($wiki->publishedWikiIdentifier()) {
            $publishedWiki ??= $this->wikiRepository->findById($wiki->publishedWikiIdentifier());
            if ($publishedWiki === null) {
                throw new WikiNotFoundException();
            }

            // スナップショット保存（更新前の状態を保存）
            $snapshot = $this->wikiSnapshotFactory->create($publishedWiki);
            $this->wikiSnapshotRepository->save($snapshot);

            $publishedWiki->setBasic($wiki->basic());
            $publishedWiki->setVersion($publishVersion);
        } else {
            $publishedWiki = $this->wikiFactory->create(
                $wiki->translationSetIdentifier(),
                $wiki->slug(),
                $wiki->language(),
                $wiki->resourceType(),
                $wiki->basic(),
                $publishVersion,
            );
        }
        $publishedWiki->setSections($wiki->sections());
        $publishedWiki->setThemeColor($wiki->themeColor());
        $publishedWiki->setFontStyle($wiki->fontStyle());
        $publishedWiki->setImageIdentifier($wiki->imageIdentifier());
        $publishedWiki->setTitle($wiki->title());
        $publishedWiki->setMetaDescription($wiki->metaDescription());
        $publishedWiki->setKeywords($wiki->keywords());
        $publishedWiki->setEditorIdentifier($wiki->editorIdentifier());
        $publishedWiki->setApproverIdentifier($wiki->approverIdentifier());
        $publishedWiki->setMergerIdentifier($wiki->mergerIdentifier());
        $publishedWiki->setMergedAt($wiki->mergedAt());
        $publishedWiki->setSourceEditorIdentifier($wiki->sourceEditorIdentifier());
        $publishedWiki->setTranslatedAt($wiki->translatedAt());
        $publishedWiki->setApprovedAt($wiki->approvedAt());
        $publishedWiki->setPublishedAt(new DateTimeImmutable());

        $this->wikiRepository->save($publishedWiki);
        $this->videoLinkCollectionRegistrationService->register(
            $publishedWiki->resourceType(),
            $publishedWiki->wikiIdentifier(),
        );

        $history = $this->wikiHistoryFactory->create(
            actionType: HistoryActionType::Publish,
            actorIdentifier: $principalIdentifier,
            submitterIdentifier: $wiki->editorIdentifier(),
            wikiIdentifier: $publishedWiki->wikiIdentifier(),
            draftWikiIdentifier: new DraftWikiIdentifier((string) $wiki->wikiIdentifier()),
            fromStatus: $wiki->status(),
            toStatus: null,
            fromVersion: null,
            toVersion: null,
            subjectName: $wiki->basic()->name(),
            visitorLocation: $visitorLocation,
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
                wikiIdentifier: $publishedWiki->wikiIdentifier(),
                isNewCreation: $isNewCreation,
            );
        }

        $this->draftWikiRepository->delete($wiki);

        $this->wikiWorkflowNotificationService->notifyPublished(
            $wiki->wikiIdentifier(),
            (string) $wiki->basic()->name(),
            $publishedWiki->slug(),
            $publishedWiki->language(),
        );

        return $publishedWiki;
    }
}
