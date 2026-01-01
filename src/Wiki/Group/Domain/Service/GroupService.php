<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Domain\Service;

use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Group\Domain\Repository\DraftGroupRepositoryInterface;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\GroupIdentifier;

readonly class GroupService implements GroupServiceInterface
{
    public function __construct(
        private DraftGroupRepositoryInterface $groupRepository,
    ) {
    }

    public function existsApprovedButNotTranslatedGroup(
        TranslationSetIdentifier $translationSetIdentifier,
        GroupIdentifier $excludeGroupIdentifier,
    ): bool {
        $draftGroups = $this->groupRepository->findByTranslationSet(
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
