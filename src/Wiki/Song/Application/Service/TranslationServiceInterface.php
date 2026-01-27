<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\Service;

use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Song\Domain\Entity\Song;

interface TranslationServiceInterface
{
    /**
     * 外部翻訳サービスを使ってSongを翻訳しTranslatedSongDataを返す
     *
     * @param Song $song
     * @param Language $targetLanguage
     * @return TranslatedSongData
     */
    public function translateSong(
        Song     $song,
        Language $targetLanguage,
    ): TranslatedSongData;
}
