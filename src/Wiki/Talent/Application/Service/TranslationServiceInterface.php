<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Application\Service;

use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Talent\Domain\Entity\Talent;

interface TranslationServiceInterface
{
    /**
     * 外部翻訳サービスを使ってTalentを翻訳しTranslatedTalentDataを返す
     *
     * @param Talent $talent
     * @param Language $targetLanguage
     * @return TranslatedTalentData
     */
    public function translateTalent(
        Talent   $talent,
        Language $targetLanguage,
    ): TranslatedTalentData;
}
