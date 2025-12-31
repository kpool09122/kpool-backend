<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Domain\Factory;

use DateTimeImmutable;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;
use Source\Wiki\Talent\Domain\Entity\TalentHistory;
use Source\Wiki\Talent\Domain\ValueObject\TalentHistoryIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\TalentName;

readonly class TalentHistoryFactory implements TalentHistoryFactoryInterface
{
    public function __construct(
        private UuidGeneratorInterface $ulidGenerator,
    ) {
    }

    public function create(
        EditorIdentifier $editorIdentifier,
        ?EditorIdentifier $submitterIdentifier,
        ?TalentIdentifier $talentIdentifier,
        ?TalentIdentifier $draftTalentIdentifier,
        ?ApprovalStatus $fromStatus,
        ?ApprovalStatus $toStatus,
        TalentName $subjectName,
    ): TalentHistory {
        return new TalentHistory(
            new TalentHistoryIdentifier($this->ulidGenerator->generate()),
            $editorIdentifier,
            $submitterIdentifier,
            $talentIdentifier,
            $draftTalentIdentifier,
            $fromStatus,
            $toStatus,
            $subjectName,
            new DateTimeImmutable('now'),
        );
    }
}
