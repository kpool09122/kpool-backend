<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Infrastructure\Service;

use Application\Http\Client\GoogleTranslateClient\GoogleTranslateClient;
use Application\Http\Client\GoogleTranslateClient\TranslateTexts\TranslateTextsRequest;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Talent\Application\Service\TranslatedTalentData;
use Source\Wiki\Talent\Application\Service\TranslationServiceInterface;
use Source\Wiki\Talent\Domain\Entity\Talent;

readonly class GoogleTranslationService implements TranslationServiceInterface
{
    public function __construct(
        private GoogleTranslateClient $googleTranslateClient,
    ) {
    }

    public function translateTalent(Talent $talent, Language $targetLanguage): TranslatedTalentData
    {
        $textsToTranslate = [
            (string) $talent->name(),
            (string) $talent->realName(),
            (string) $talent->career(),
        ];

        $request = new TranslateTextsRequest(
            texts: $textsToTranslate,
            targetLanguage: $targetLanguage->value,
        );

        $response = $this->googleTranslateClient->translateTexts($request);
        $translations = $response->translatedTexts();

        return new TranslatedTalentData(
            translatedName: $translations[0] ?? (string) $talent->name(),
            translatedRealName: $translations[1] ?? (string) $talent->realName(),
            translatedCareer: $translations[2] ?? (string) $talent->career(),
        );
    }
}
