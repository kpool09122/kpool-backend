<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Domain\Service;

use Source\Wiki\Group\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\TranslationSetIdentifier;

interface GroupServiceInterface
{
    /**
     * 同じ翻訳セットの別版で、承認済み（Approved）だが公開されていないDraftGroupが存在するかチェック
     *
     * @param TranslationSetIdentifier $translationSetIdentifier
     * @param GroupIdentifier $excludeGroupIdentifier
     * @return bool
     */
    public function existsApprovedButNotTranslatedGroup(
        TranslationSetIdentifier $translationSetIdentifier,
        GroupIdentifier $excludeGroupIdentifier,
    ): bool;
}
