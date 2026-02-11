<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Domain\Service;

use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Wiki\Domain\Repository\DraftWikiRepositoryInterface;
use Source\Wiki\Wiki\Domain\Repository\WikiRepositoryInterface;
use Source\Wiki\Wiki\Domain\ValueObject\DraftWikiIdentifier;

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

    public function existsApprovedDraftWiki(
        TranslationSetIdentifier $translationSetIdentifier,
        DraftWikiIdentifier $excludeWikiIdentifier,
    ): bool {
        $draftWikis = $this->draftWikiRepository->findByTranslationSetIdentifier(
            $translationSetIdentifier,
        );

        foreach ($draftWikis as $draftWiki) {
            // 自分自身は除外
            if ((string) $draftWiki->wikiIdentifier() === (string) $excludeWikiIdentifier) {
                continue;
            }

            // Approved状態のものが存在すればtrue
            if ($draftWiki->status() === ApprovalStatus::Approved) {
                return true;
            }
        }

        return false;
    }
}
