<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Domain\Repository;

use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Group\Domain\Entity\DraftGroup;
use Source\Wiki\Shared\Domain\ValueObject\GroupIdentifier;

interface DraftGroupRepositoryInterface
{
    public function findById(GroupIdentifier $groupIdentifier): ?DraftGroup;

    public function save(DraftGroup $group): void;

    public function delete(DraftGroup $group): void;

    /**
     * @param TranslationSetIdentifier $translationSetIdentifier
     * @return DraftGroup[]
     */
    public function findByTranslationSet(
        TranslationSetIdentifier $translationSetIdentifier,
    ): array;
}
