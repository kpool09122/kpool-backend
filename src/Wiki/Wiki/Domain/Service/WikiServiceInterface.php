<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Domain\Service;

use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Wiki\Domain\Entity\DraftWiki;
use Source\Wiki\Wiki\Domain\ValueObject\DraftWikiIdentifier;

interface WikiServiceInterface
{
    /**
     * 同じ翻訳セットの公開Wikiのバージョンがすべて揃っているかチェック
     *
     * @param TranslationSetIdentifier $translationSetIdentifier
     * @return bool
     */
    public function hasConsistentVersions(
        TranslationSetIdentifier $translationSetIdentifier,
    ): bool;

    /**
     * 同じ翻訳セットの別版で、承認済み（Approved）だが公開されていないDraftWikiが存在するかチェック
     *
     * @param TranslationSetIdentifier $translationSetIdentifier
     * @param DraftWikiIdentifier $excludeWikiIdentifier
     * @return bool
     */
    public function existsApprovedDraftWiki(
        TranslationSetIdentifier $translationSetIdentifier,
        DraftWikiIdentifier $excludeWikiIdentifier,
    ): bool;

    public function canApproveDraftWiki(DraftWiki $draftWiki): bool;

    public function resolvePublishVersion(DraftWiki $draftWiki): ?Version;
}
