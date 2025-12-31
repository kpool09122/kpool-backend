<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Domain\Factory;

use DateTimeImmutable;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Wiki\Group\Domain\Entity\GroupHistory;
use Source\Wiki\Group\Domain\ValueObject\GroupHistoryIdentifier;
use Source\Wiki\Group\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Group\Domain\ValueObject\GroupName;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;

readonly class GroupHistoryFactory implements GroupHistoryFactoryInterface
{
    public function __construct(
        private UuidGeneratorInterface $ulidGenerator,
    ) {
    }

    public function create(
        EditorIdentifier $editorIdentifier,
        ?EditorIdentifier $submitterIdentifier,
        ?GroupIdentifier $groupIdentifier,
        ?GroupIdentifier $draftGroupIdentifier,
        ?ApprovalStatus $fromStatus,
        ?ApprovalStatus $toStatus,
        GroupName $subjectName,
    ): GroupHistory {
        return new GroupHistory(
            new GroupHistoryIdentifier($this->ulidGenerator->generate()),
            $editorIdentifier,
            $submitterIdentifier,
            $groupIdentifier,
            $draftGroupIdentifier,
            $fromStatus,
            $toStatus,
            $subjectName,
            new DateTimeImmutable('now'),
        );
    }
}
