<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Application\Service;

use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Agency\Domain\Entity\Agency;

interface TranslationServiceInterface
{
    /**
     * 外部翻訳サービスを使ってAgencyを翻訳しTranslatedAgencyDataを返す
     *
     * @param Agency $agency
     * @param Language $targetLanguage
     * @return TranslatedAgencyData
     */
    public function translateAgency(
        Agency   $agency,
        Language $targetLanguage,
    ): TranslatedAgencyData;
}
