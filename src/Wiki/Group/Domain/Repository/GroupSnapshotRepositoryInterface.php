<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Domain\Repository;

use Source\Wiki\Group\Domain\Entity\GroupSnapshot;
use Source\Wiki\Shared\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;

interface GroupSnapshotRepositoryInterface
{
    public function save(GroupSnapshot $snapshot): void;

    /**
     * @param GroupIdentifier $groupIdentifier
     * @return GroupSnapshot[]
     */
    public function findByGroupIdentifier(GroupIdentifier $groupIdentifier): array;

    public function findByGroupAndVersion(
        GroupIdentifier $groupIdentifier,
        Version $version
    ): ?GroupSnapshot;
}
