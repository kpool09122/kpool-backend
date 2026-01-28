<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Infrastructure\Service;

use Application\Http\Client\GoogleTranslateClient\GoogleTranslateClient;
use Application\Http\Client\GoogleTranslateClient\TranslateTexts\TranslateTextsRequest;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Group\Application\Service\TranslatedGroupData;
use Source\Wiki\Group\Application\Service\TranslationServiceInterface;
use Source\Wiki\Group\Domain\Entity\Group;

readonly class GoogleTranslationService implements TranslationServiceInterface
{
    public function __construct(
        private GoogleTranslateClient $googleTranslateClient,
    ) {
    }

    public function translateGroup(Group $group, Language $targetLanguage): TranslatedGroupData
    {
        $textsToTranslate = [
            (string) $group->name(),
            (string) $group->description(),
        ];

        $request = new TranslateTextsRequest(
            texts: $textsToTranslate,
            targetLanguage: $targetLanguage->value,
        );

        $response = $this->googleTranslateClient->translateTexts($request);
        $translations = $response->translatedTexts();

        return new TranslatedGroupData(
            translatedName: $translations[0] ?? (string) $group->name(),
            translatedDescription: $translations[1] ?? (string) $group->description(),
        );
    }
}
