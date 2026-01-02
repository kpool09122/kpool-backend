<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Domain\Entity;

use DateTimeImmutable;
use InvalidArgumentException;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\HistoryActionType;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Song\Domain\ValueObject\SongHistoryIdentifier;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;
use Source\Wiki\Song\Domain\ValueObject\SongName;

readonly class SongHistory
{
    public function __construct(
        private SongHistoryIdentifier $historyIdentifier,
        private HistoryActionType     $actionType,
        private PrincipalIdentifier   $editorIdentifier,
        private ?PrincipalIdentifier  $submitterIdentifier,
        private ?SongIdentifier       $songIdentifier,
        private ?SongIdentifier       $draftSongIdentifier,
        private ?ApprovalStatus       $fromStatus,
        private ?ApprovalStatus       $toStatus,
        private ?Version              $fromVersion,
        private ?Version              $toVersion,
        private SongName              $subjectName,
        private DateTimeImmutable     $recordedAt
    ) {
        $this->validate($songIdentifier, $draftSongIdentifier);
    }

    private function validate(?SongIdentifier $songIdentifier, ?SongIdentifier $draftSongIdentifier): void
    {
        if ($songIdentifier === null && $draftSongIdentifier === null) {
            throw new InvalidArgumentException('At least one of song identifier or draft identifier must be provided.');
        }
    }

    public function historyIdentifier(): SongHistoryIdentifier
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

    public function songIdentifier(): ?SongIdentifier
    {
        return $this->songIdentifier;
    }

    public function draftSongIdentifier(): ?SongIdentifier
    {
        return $this->draftSongIdentifier;
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

    public function subjectName(): SongName
    {
        return $this->subjectName;
    }

    public function recordedAt(): DateTimeImmutable
    {
        return $this->recordedAt;
    }
}
