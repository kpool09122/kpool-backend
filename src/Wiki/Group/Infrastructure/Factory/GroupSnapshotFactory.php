<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Infrastructure\Factory;

use DateTimeImmutable;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Wiki\Group\Domain\Entity\Group;
use Source\Wiki\Group\Domain\Entity\GroupSnapshot;
use Source\Wiki\Group\Domain\Factory\GroupSnapshotFactoryInterface;
use Source\Wiki\Group\Domain\ValueObject\GroupSnapshotIdentifier;

readonly class GroupSnapshotFactory implements GroupSnapshotFactoryInterface
{
    public function __construct(
        private UuidGeneratorInterface $ulidGenerator,
    ) {
    }

    public function create(Group $group): GroupSnapshot
    {
        return new GroupSnapshot(
            new GroupSnapshotIdentifier($this->ulidGenerator->generate()),
            $group->groupIdentifier(),
            $group->translationSetIdentifier(),
            $group->language(),
            $group->name(),
            $group->normalizedName(),
            $group->agencyIdentifier(),
            $group->description(),
            $group->songIdentifiers(),
            $group->imagePath(),
            $group->version(),
            new DateTimeImmutable('now'),
        );
    }
}
