<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Domain\Service;

use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Group\Domain\Repository\GroupRepositoryInterface;
use Source\Wiki\Group\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;

class GroupService implements GroupServiceInterface
{
    public function __construct(
        private readonly GroupRepositoryInterface $groupRepository,
    ) {
    }

    public function existsApprovedButNotTranslatedGroup(
        TranslationSetIdentifier $translationSetIdentifier,
        GroupIdentifier $excludeGroupIdentifier,
    ): bool {
        $draftGroups = $this->groupRepository->findDraftsByTranslationSet(
            $translationSetIdentifier,
        );

        foreach ($draftGroups as $draftGroup) {
            // 自分自身は除外
            if ((string) $draftGroup->groupIdentifier() === (string) $excludeGroupIdentifier) {
                continue;
            }

            // Approved状態のものが存在すればtrue
            if ($draftGroup->status() === ApprovalStatus::Approved) {
                return true;
            }
        }

        return false;
    }
}
