<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Domain\Repository;

use Source\Wiki\Group\Domain\Entity\DraftGroup;
use Source\Wiki\Group\Domain\Entity\Group;
use Source\Wiki\Group\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\TranslationSetIdentifier;

interface GroupRepositoryInterface
{
    public function findById(GroupIdentifier $groupIdentifier): ?Group;

    public function findDraftById(GroupIdentifier $groupIdentifier): ?DraftGroup;

    public function save(Group $group): void;

    public function saveDraft(DraftGroup $group): void;

    public function deleteDraft(DraftGroup $group): void;

    /**
     * @param TranslationSetIdentifier $translationSetIdentifier
     * @return DraftGroup[]
     */
    public function findDraftsByTranslationSet(
        TranslationSetIdentifier $translationSetIdentifier,
    ): array;
}
