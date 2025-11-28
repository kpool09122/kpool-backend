<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\Service;

use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Song\Domain\Entity\DraftSong;
use Source\Wiki\Song\Domain\Entity\Song;

interface TranslationServiceInterface
{
    /**
     * 外部翻訳サービスを使ってSongを翻訳しDraftSongを作成
     *
     * @param Song $song
     * @param Language $language
     * @return DraftSong
     */
    public function translateSong(
        Song     $song,
        Language $language,
    ): DraftSong;
}
