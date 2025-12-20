<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Domain\Entity;

use DateTimeImmutable;
use InvalidArgumentException;
use Source\Wiki\Agency\Domain\ValueObject\AgencyHistoryIdentifier;
use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;

readonly class AgencyHistory
{
    public function __construct(
        private AgencyHistoryIdentifier $historyIdentifier,
        private EditorIdentifier        $editorIdentifier,
        private ?EditorIdentifier       $submitterIdentifier,
        private ?AgencyIdentifier       $agencyIdentifier,
        private ?AgencyIdentifier       $draftAgencyIdentifier,
        private ?ApprovalStatus         $fromStatus,
        private ApprovalStatus          $toStatus,
        private DateTimeImmutable       $recordedAt
    ) {
        $this->validate($agencyIdentifier, $draftAgencyIdentifier);
    }

    private function validate(?AgencyIdentifier $agencyIdentifier, ?AgencyIdentifier $draftAgencyIdentifier): void
    {
        if ($agencyIdentifier === null && $draftAgencyIdentifier === null) {
            throw new InvalidArgumentException('At least one of agency identifier or draft identifier must be provided.');
        }
    }

    public function historyIdentifier(): AgencyHistoryIdentifier
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

    public function agencyIdentifier(): ?AgencyIdentifier
    {
        return $this->agencyIdentifier;
    }

    public function draftAgencyIdentifier(): ?AgencyIdentifier
    {
        return $this->draftAgencyIdentifier;
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
