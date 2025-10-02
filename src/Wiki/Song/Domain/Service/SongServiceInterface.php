<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Domain\Service;

use Source\Wiki\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;

interface SongServiceInterface
{
    /**
     * 同じ翻訳セットの別版で、承認済み（Approved）だが公開されていないDraftSongが存在するかチェック
     *
     * @param TranslationSetIdentifier $translationSetIdentifier
     * @param SongIdentifier $excludeSongIdentifier
     * @return bool
     */
    public function existsApprovedButNotTranslatedSong(
        TranslationSetIdentifier $translationSetIdentifier,
        SongIdentifier $excludeSongIdentifier,
    ): bool;
}
