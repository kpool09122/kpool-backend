<?php

declare(strict_types=1);

namespace Source\Wiki\Member\Domain\Service;

use Source\Wiki\Member\Domain\Repository\MemberRepositoryInterface;
use Source\Wiki\Member\Domain\ValueObject\MemberIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\TranslationSetIdentifier;

class MemberService implements MemberServiceInterface
{
    public function __construct(
        private readonly MemberRepositoryInterface $memberRepository,
    ) {
    }

    public function existsApprovedButNotTranslatedMember(
        TranslationSetIdentifier $translationSetIdentifier,
        MemberIdentifier $excludeMemberIdentifier,
    ): bool {
        $draftMembers = $this->memberRepository->findDraftsByTranslationSet(
            $translationSetIdentifier,
        );

        foreach ($draftMembers as $draftMember) {
            // 自分自身は除外
            if ((string) $draftMember->memberIdentifier() === (string) $excludeMemberIdentifier) {
                continue;
            }

            // Approved状態のものが存在すればtrue
            if ($draftMember->status() === ApprovalStatus::Approved) {
                return true;
            }
        }

        return false;
    }
}
