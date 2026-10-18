<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Domain\Service;

use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Wiki\Domain\Entity\DraftWiki;

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
     * 同じ翻訳元の別言語版を除き、競合する承認済み下書きが存在するかチェック
     *
     * @param DraftWiki $draftWiki
     * @return bool
     */
    public function existsConflictingApprovedDraftWiki(
        DraftWiki $draftWiki,
    ): bool;

    public function canApproveDraftWiki(DraftWiki $draftWiki): bool;

    public function resolvePublishVersion(DraftWiki $draftWiki): ?Version;
}
