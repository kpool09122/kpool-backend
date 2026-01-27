<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Infrastructure\Service;

use Application\Http\Client\GoogleTranslateClient\GoogleTranslateClient;
use Application\Http\Client\GoogleTranslateClient\TranslateTexts\TranslateTextsRequest;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Agency\Application\Service\TranslatedAgencyData;
use Source\Wiki\Agency\Application\Service\TranslationServiceInterface;
use Source\Wiki\Agency\Domain\Entity\Agency;

readonly class GoogleTranslationService implements TranslationServiceInterface
{
    public function __construct(
        private GoogleTranslateClient $googleTranslateClient,
    ) {
    }

    public function translateAgency(Agency $agency, Language $targetLanguage): TranslatedAgencyData
    {
        $textsToTranslate = [
            (string) $agency->name(),
            (string) $agency->CEO(),
            (string) $agency->description(),
        ];

        $request = new TranslateTextsRequest(
            texts: $textsToTranslate,
            targetLanguage: $targetLanguage->value,
        );

        $response = $this->googleTranslateClient->translateTexts($request);
        $translations = $response->translatedTexts();

        return new TranslatedAgencyData(
            translatedName: $translations[0] ?? (string) $agency->name(),
            translatedCEO: $translations[1] ?? (string) $agency->CEO(),
            translatedDescription: $translations[2] ?? (string) $agency->description(),
        );
    }
}
