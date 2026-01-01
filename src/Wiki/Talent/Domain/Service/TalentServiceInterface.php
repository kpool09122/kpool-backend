<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Domain\Service;

use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\TalentIdentifier;

interface TalentServiceInterface
{
    /**
     * 同じ翻訳セットの別版で、承認済み（Approved）だが公開されていないDraftTalentが存在するかチェック
     *
     * @param TranslationSetIdentifier $translationSetIdentifier
     * @param TalentIdentifier $excludeTalentIdentifier
     * @return bool
     */
    public function existsApprovedButNotTranslatedTalent(
        TranslationSetIdentifier $translationSetIdentifier,
        TalentIdentifier         $excludeTalentIdentifier,
    ): bool;
}
