<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Domain\Entity;

use DateTimeImmutable;
use InvalidArgumentException;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\HistoryActionType;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\Name;
use Source\Wiki\Wiki\Domain\ValueObject\DraftWikiIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\WikiHistoryIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

readonly class WikiHistory
{
    public function __construct(
        private WikiHistoryIdentifier $historyIdentifier,
        private HistoryActionType     $actionType,
        private PrincipalIdentifier   $actorIdentifier,
        private ?PrincipalIdentifier  $submitterIdentifier,
        private ?WikiIdentifier       $wikiIdentifier,
        private ?DraftWikiIdentifier  $draftWikiIdentifier,
        private ?ApprovalStatus       $fromStatus,
        private ?ApprovalStatus       $toStatus,
        private ?Version              $fromVersion,
        private ?Version              $toVersion,
        private Name                  $subjectName,
        private DateTimeImmutable     $recordedAt,
    ) {
        $this->validate($wikiIdentifier, $draftWikiIdentifier);
    }

    private function validate(?WikiIdentifier $wikiIdentifier, ?DraftWikiIdentifier $draftWikiIdentifier): void
    {
        if ($wikiIdentifier === null && $draftWikiIdentifier === null) {
            throw new InvalidArgumentException('At least one of wiki identifier or draft identifier must be provided.');
        }
    }

    public function historyIdentifier(): WikiHistoryIdentifier
    {
        return $this->historyIdentifier;
    }

    public function actionType(): HistoryActionType
    {
        return $this->actionType;
    }

    public function actorIdentifier(): PrincipalIdentifier
    {
        return $this->actorIdentifier;
    }

    public function submitterIdentifier(): ?PrincipalIdentifier
    {
        return $this->submitterIdentifier;
    }

    public function wikiIdentifier(): ?WikiIdentifier
    {
        return $this->wikiIdentifier;
    }

    public function draftWikiIdentifier(): ?DraftWikiIdentifier
    {
        return $this->draftWikiIdentifier;
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

    public function subjectName(): Name
    {
        return $this->subjectName;
    }

    public function recordedAt(): DateTimeImmutable
    {
        return $this->recordedAt;
    }
}
