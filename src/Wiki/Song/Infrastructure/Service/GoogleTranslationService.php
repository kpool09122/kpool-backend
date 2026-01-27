<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Infrastructure\Service;

use Application\Http\Client\GoogleTranslateClient\GoogleTranslateClient;
use Application\Http\Client\GoogleTranslateClient\TranslateTexts\TranslateTextsRequest;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Song\Application\Service\TranslatedSongData;
use Source\Wiki\Song\Application\Service\TranslationServiceInterface;
use Source\Wiki\Song\Domain\Entity\Song;

readonly class GoogleTranslationService implements TranslationServiceInterface
{
    public function __construct(
        private GoogleTranslateClient $googleTranslateClient,
    ) {
    }

    public function translateSong(Song $song, Language $targetLanguage): TranslatedSongData
    {
        $textsToTranslate = [
            (string) $song->name(),
            (string) $song->lyricist(),
            (string) $song->composer(),
            (string) $song->overView(),
        ];

        $request = new TranslateTextsRequest(
            texts: $textsToTranslate,
            targetLanguage: $targetLanguage->value,
        );

        $response = $this->googleTranslateClient->translateTexts($request);
        $translations = $response->translatedTexts();

        return new TranslatedSongData(
            translatedName: $translations[0] ?? (string) $song->name(),
            translatedLyricist: $translations[1] ?? (string) $song->lyricist(),
            translatedComposer: $translations[2] ?? (string) $song->composer(),
            translatedOverview: $translations[3] ?? (string) $song->overView(),
        );
    }
}
