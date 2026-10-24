<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Domain\Service;

use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Wiki\Domain\Entity\DraftWiki;
use Source\Wiki\Wiki\Domain\Entity\Wiki;
use Source\Wiki\Wiki\Domain\Repository\DraftWikiRepositoryInterface;
use Source\Wiki\Wiki\Domain\Repository\WikiRepositoryInterface;

readonly class WikiService implements WikiServiceInterface
{
    public function __construct(
        private WikiRepositoryInterface      $wikiRepository,
        private DraftWikiRepositoryInterface  $draftWikiRepository,
    ) {
    }

    public function hasConsistentVersions(
        TranslationSetIdentifier $translationSetIdentifier,
    ): bool {
        $wikis = $this->wikiRepository->findByTranslationSetIdentifier(
            $translationSetIdentifier,
        );

        if (count($wikis) <= 1) {
            return true;
        }

        $firstVersion = $wikis[0]->version();

        return array_all($wikis, fn ($wiki) => $wiki->hasSameVersion($firstVersion));
    }

    public function existsConflictingApprovedDraftWiki(
        DraftWiki $draftWiki,
    ): bool {
        $draftWikis = $this->draftWikiRepository->findByTranslationSetIdentifier(
            $draftWiki->translationSetIdentifier(),
        );

        foreach ($draftWikis as $otherDraftWiki) {
            if ((string) $otherDraftWiki->wikiIdentifier() === (string) $draftWiki->wikiIdentifier()) {
                continue;
            }

            if ($otherDraftWiki->status() === ApprovalStatus::Approved
                && ! $draftWiki->sharesTranslationSourceWith($otherDraftWiki)) {
                return true;
            }
        }

        return false;
    }

    public function canApproveDraftWiki(DraftWiki $draftWiki): bool
    {
        return $draftWiki->publishedWikiIdentifier() !== null || $this->resolvePublishVersion($draftWiki) !== null;
    }

    public function resolvePublishVersion(DraftWiki $draftWiki): ?Version
    {
        $publishedWikis = $this->wikiRepository->findByTranslationSetIdentifier(
            $draftWiki->translationSetIdentifier(),
        );

        if ($publishedWikis === []) {
            return new Version(1);
        }

        $latestVersion = $this->latestVersion($publishedWikis);
        $targetWiki = $this->targetPublishedWiki($publishedWikis, $draftWiki);
        $isTranslationDraft = $draftWiki->translatedAt() !== null;

        if (! $isTranslationDraft) {
            if (! $this->hasSameVersions($publishedWikis)) {
                return null;
            }

            return $targetWiki ? Version::nextVersion($targetWiki->version()) : $latestVersion;
        }

        if ($targetWiki === null) {
            return $latestVersion;
        }

        if ($targetWiki->isVersionGreaterThan($latestVersion) || $targetWiki->hasSameVersion($latestVersion)) {
            return null;
        }

        return $latestVersion;
    }

    /**
     * @param Wiki[] $wikis
     */
    private function hasSameVersions(array $wikis): bool
    {
        if (count($wikis) <= 1) {
            return true;
        }

        $firstVersion = $wikis[0]->version();

        return array_all($wikis, fn (Wiki $wiki) => $wiki->hasSameVersion($firstVersion));
    }

    /**
     * @param Wiki[] $wikis
     */
    private function latestVersion(array $wikis): Version
    {
        $latest = $wikis[0]->version();

        foreach ($wikis as $wiki) {
            if ($wiki->isVersionGreaterThan($latest)) {
                $latest = $wiki->version();
            }
        }

        return $latest;
    }

    /**
     * @param Wiki[] $wikis
     */
    private function targetPublishedWiki(array $wikis, DraftWiki $draftWiki): ?Wiki
    {
        foreach ($wikis as $wiki) {
            if (
                $draftWiki->publishedWikiIdentifier() !== null
                && (string) $wiki->wikiIdentifier() === (string) $draftWiki->publishedWikiIdentifier()
            ) {
                return $wiki;
            }
        }

        foreach ($wikis as $wiki) {
            if ($wiki->language() === $draftWiki->language()) {
                return $wiki;
            }
        }

        return null;
    }
}
