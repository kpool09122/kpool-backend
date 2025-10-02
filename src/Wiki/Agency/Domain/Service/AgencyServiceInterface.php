<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Domain\Service;

use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\TranslationSetIdentifier;

interface AgencyServiceInterface
{
    /**
     * 同じ翻訳セットの別版で、承認済み（Approved）だが公開されていないDraftAgencyが存在するかチェック
     *
     * @param TranslationSetIdentifier $translationSetIdentifier
     * @param AgencyIdentifier $excludeAgencyIdentifier
     * @return bool
     */
    public function existsApprovedButNotTranslatedAgency(
        TranslationSetIdentifier $translationSetIdentifier,
        AgencyIdentifier $excludeAgencyIdentifier,
    ): bool;
}
