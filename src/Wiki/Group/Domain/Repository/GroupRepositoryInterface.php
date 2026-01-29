<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Domain\Repository;

use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Group\Domain\Entity\Group;
use Source\Wiki\Shared\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Slug;

interface GroupRepositoryInterface
{
    public function findById(GroupIdentifier $groupIdentifier): ?Group;

    /**
     * @param GroupIdentifier[] $groupIdentifiers
     * @return Group[]
     */
    public function findByIds(array $groupIdentifiers): array;

    public function existsBySlug(Slug $slug): bool;

    /**
     * @return Group[]
     */
    public function findByTranslationSetIdentifier(TranslationSetIdentifier $translationSetIdentifier): array;

    public function save(Group $group): void;
}
