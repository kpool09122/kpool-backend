<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Domain\Entity;

use DateTimeImmutable;
use InvalidArgumentException;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\HistoryActionType;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Talent\Domain\ValueObject\TalentHistoryIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\TalentName;

readonly class TalentHistory
{
    public function __construct(
        private TalentHistoryIdentifier $historyIdentifier,
        private HistoryActionType       $actionType,
        private PrincipalIdentifier     $editorIdentifier,
        private ?PrincipalIdentifier    $submitterIdentifier,
        private ?TalentIdentifier       $talentIdentifier,
        private ?TalentIdentifier       $draftTalentIdentifier,
        private ?ApprovalStatus         $fromStatus,
        private ?ApprovalStatus         $toStatus,
        private ?Version                $fromVersion,
        private ?Version                $toVersion,
        private TalentName              $subjectName,
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

    public function actionType(): HistoryActionType
    {
        return $this->actionType;
    }

    public function editorIdentifier(): PrincipalIdentifier
    {
        return $this->editorIdentifier;
    }

    public function submitterIdentifier(): ?PrincipalIdentifier
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

    public function fromVersion(): ?Version
    {
        return $this->fromVersion;
    }

    public function toVersion(): ?Version
    {
        return $this->toVersion;
    }

    public function subjectName(): TalentName
    {
        return $this->subjectName;
    }

    public function recordedAt(): DateTimeImmutable
    {
        return $this->recordedAt;
    }
}
