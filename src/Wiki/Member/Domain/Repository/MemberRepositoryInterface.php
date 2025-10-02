<?php

declare(strict_types=1);

namespace Source\Wiki\Member\Domain\Repository;

use Source\Wiki\Member\Domain\Entity\DraftMember;
use Source\Wiki\Member\Domain\Entity\Member;
use Source\Wiki\Member\Domain\ValueObject\MemberIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\TranslationSetIdentifier;

interface MemberRepositoryInterface
{
    public function findById(MemberIdentifier $identifier): ?Member;

    public function save(Member $member): void;

    public function findDraftById(MemberIdentifier $identifier): ?DraftMember;

    public function saveDraft(DraftMember $member): void;

    public function deleteDraft(DraftMember $member): void;

    /**
     * @param TranslationSetIdentifier $translationSetIdentifier
     * @return DraftMember[]
     */
    public function findDraftsByTranslationSet(
        TranslationSetIdentifier $translationSetIdentifier,
    ): array;
}
