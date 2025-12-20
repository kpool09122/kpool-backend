<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Domain\Entity;

use DateTimeImmutable;
use InvalidArgumentException;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\TalentHistoryIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\TalentIdentifier;

readonly class TalentHistory
{
    public function __construct(
        private TalentHistoryIdentifier $historyIdentifier,
        private EditorIdentifier        $editorIdentifier,
        private ?EditorIdentifier       $submitterIdentifier,
        private ?TalentIdentifier       $talentIdentifier,
        private ?TalentIdentifier       $draftTalentIdentifier,
        private ?ApprovalStatus         $fromStatus,
        private ApprovalStatus          $toStatus,
        private DateTimeImmutable       $recordedAt
    ) {
        $this->validate($talentIdentifier, $draftTalentIdentifier);
    }

    private function validate(?TalentIdentifier $talentIdentifier, ?TalentIdentifier $draftTalentIdentifier): void
    {
        if ($talentIdentifier === null && $draftTalentIdentifier === null) {
            throw new InvalidArgumentException('At least one of talent identifier or draft identifier must be provided.');
        }
    }

    public function historyIdentifier(): TalentHistoryIdentifier
    {
        return $this->historyIdentifier;
    }

    public function editorIdentifier(): EditorIdentifier
    {
        return $this->editorIdentifier;
    }

    public function submitterIdentifier(): ?EditorIdentifier
    {
        return $this->submitterIdentifier;
    }

    public function talentIdentifier(): ?TalentIdentifier
    {
        return $this->talentIdentifier;
    }

    public function draftTalentIdentifier(): ?TalentIdentifier
    {
        return $this->draftTalentIdentifier;
    }

    public function fromStatus(): ?ApprovalStatus
    {
        return $this->fromStatus;
    }

    public function toStatus(): ?ApprovalStatus
    {
        return $this->toStatus;
    }

    public function recordedAt(): DateTimeImmutable
    {
        return $this->recordedAt;
    }
}
