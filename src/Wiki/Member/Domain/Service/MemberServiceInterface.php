<?php

declare(strict_types=1);

namespace Source\Wiki\Member\Domain\Service;

use Source\Wiki\Member\Domain\ValueObject\MemberIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\TranslationSetIdentifier;

interface MemberServiceInterface
{
    /**
     * 同じ翻訳セットの別版で、承認済み（Approved）だが公開されていないDraftMemberが存在するかチェック
     *
     * @param TranslationSetIdentifier $translationSetIdentifier
     * @param MemberIdentifier $excludeMemberIdentifier
     * @return bool
     */
    public function existsApprovedButNotTranslatedMember(
        TranslationSetIdentifier $translationSetIdentifier,
        MemberIdentifier $excludeMemberIdentifier,
    ): bool;
}
