<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Application\Service;

use Source\Shared\Domain\ValueObject\Translation;
use Source\Wiki\Agency\Domain\Entity\Agency;
use Source\Wiki\Agency\Domain\Entity\DraftAgency;

interface TranslationServiceInterface
{
    /**
     * 外部翻訳サービスを使ってAgencyを翻訳しDraftAgencyを作成
     *
     * @param Agency $agency
     * @param Translation $translation
     * @return DraftAgency
     */
    public function translateAgency(
        Agency $agency,
        Translation $translation,
    ): DraftAgency;
}
