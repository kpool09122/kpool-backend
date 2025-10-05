<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Application\Service;

use Source\Shared\Domain\ValueObject\Translation;
use Source\Wiki\Talent\Domain\Entity\DraftTalent;
use Source\Wiki\Talent\Domain\Entity\Talent;

interface TranslationServiceInterface
{
    /**
     * 外部翻訳サービスを使ってTalentを翻訳しDraftTalentを作成
     *
     * @param Talent $talent
     * @param Translation $translation
     * @return DraftTalent
     */
    public function translateTalent(
        Talent      $talent,
        Translation $translation,
    ): DraftTalent;
}
