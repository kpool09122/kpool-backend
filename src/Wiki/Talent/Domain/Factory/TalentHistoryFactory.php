<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Domain\Factory;

use DateTimeImmutable;
use Source\Shared\Application\Service\Ulid\UlidGeneratorInterface;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;
use Source\Wiki\Talent\Domain\Entity\TalentHistory;
use Source\Wiki\Talent\Domain\ValueObject\TalentHistoryIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\TalentIdentifier;

readonly class TalentHistoryFactory implements TalentHistoryFactoryInterface
{
    public function __construct(
        private UlidGeneratorInterface $ulidGenerator,
    ) {
    }

    public function create(
        EditorIdentifier $editorIdentifier,
        ?EditorIdentifier $submitterIdentifier,
        ?TalentIdentifier $talentIdentifier,
        ?TalentIdentifier $draftTalentIdentifier,
        ?ApprovalStatus $fromStatus,
        ApprovalStatus $toStatus,
    ): TalentHistory {
        return new TalentHistory(
            new TalentHistoryIdentifier($this->ulidGenerator->generate()),
            $editorIdentifier,
            $submitterIdentifier,
            $talentIdentifier,
            $draftTalentIdentifier,
            $fromStatus,
            $toStatus,
            new DateTimeImmutable('now'),
        );
    }
}
