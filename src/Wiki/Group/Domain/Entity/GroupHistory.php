<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Domain\Entity;

use DateTimeImmutable;
use InvalidArgumentException;
use Source\Wiki\Group\Domain\ValueObject\GroupHistoryIdentifier;
use Source\Wiki\Group\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Group\Domain\ValueObject\GroupName;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

readonly class GroupHistory
{
    public function __construct(
        private GroupHistoryIdentifier $historyIdentifier,
        private PrincipalIdentifier    $editorIdentifier,
        private ?PrincipalIdentifier   $submitterIdentifier,
        private ?GroupIdentifier       $groupIdentifier,
        private ?GroupIdentifier       $draftGroupIdentifier,
        private ?ApprovalStatus        $fromStatus,
        private ?ApprovalStatus        $toStatus,
        private GroupName              $subjectName,
        private DateTimeImmutable      $recordedAt
    ) {
        $this->validate($groupIdentifier, $draftGroupIdentifier);
    }

    private function validate(?GroupIdentifier $groupIdentifier, ?GroupIdentifier $draftGroupIdentifier): void
    {
        if ($groupIdentifier === null && $draftGroupIdentifier === null) {
            throw new InvalidArgumentException('At least one of group identifier or draft identifier must be provided.');
        }
    }

    public function historyIdentifier(): GroupHistoryIdentifier
    {
        return $this->historyIdentifier;
    }

    public function editorIdentifier(): PrincipalIdentifier
    {
        return $this->editorIdentifier;
    }

    public function submitterIdentifier(): ?PrincipalIdentifier
    {
        return $this->submitterIdentifier;
    }

    public function groupIdentifier(): ?GroupIdentifier
    {
        return $this->groupIdentifier;
    }

    public function draftGroupIdentifier(): ?GroupIdentifier
    {
        return $this->draftGroupIdentifier;
    }

    public function fromStatus(): ?ApprovalStatus
    {
        return $this->fromStatus;
    }

    public function toStatus(): ?ApprovalStatus
    {
        return $this->toStatus;
    }

    public function subjectName(): GroupName
    {
        return $this->subjectName;
    }

    public function recordedAt(): DateTimeImmutable
    {
        return $this->recordedAt;
    }
}
