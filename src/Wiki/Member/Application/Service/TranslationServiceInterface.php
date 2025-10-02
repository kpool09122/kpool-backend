<?php

declare(strict_types=1);

namespace Source\Wiki\Member\Application\Service;

use Source\Shared\Domain\ValueObject\Translation;
use Source\Wiki\Member\Domain\Entity\DraftMember;
use Source\Wiki\Member\Domain\Entity\Member;

interface TranslationServiceInterface
{
    /**
     * 外部翻訳サービスを使ってMemberを翻訳しDraftMemberを作成
     *
     * @param Member $member
     * @param Translation $translation
     * @return DraftMember
     */
    public function translateMember(
        Member $member,
        Translation $translation,
    ): DraftMember;
}
