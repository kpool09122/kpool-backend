<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Domain\Factory;

use DateTimeImmutable;
use Source\Shared\Application\Service\Ulid\UlidGeneratorInterface;
use Source\Wiki\Group\Domain\Entity\GroupHistory;
use Source\Wiki\Group\Domain\ValueObject\GroupHistoryIdentifier;
use Source\Wiki\Group\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;

readonly class GroupHistoryFactory implements GroupHistoryFactoryInterface
{
    public function __construct(
        private UlidGeneratorInterface $ulidGenerator,
    ) {
    }

    public function create(
        EditorIdentifier $editorIdentifier,
        ?EditorIdentifier $submitterIdentifier,
        ?GroupIdentifier $groupIdentifier,
        ?GroupIdentifier $draftGroupIdentifier,
        ?ApprovalStatus $fromStatus,
        ApprovalStatus $toStatus,
    ): GroupHistory {
        return new GroupHistory(
            new GroupHistoryIdentifier($this->ulidGenerator->generate()),
            $editorIdentifier,
            $submitterIdentifier,
            $groupIdentifier,
            $draftGroupIdentifier,
            $fromStatus,
            $toStatus,
            new DateTimeImmutable('now'),
        );
    }
}
