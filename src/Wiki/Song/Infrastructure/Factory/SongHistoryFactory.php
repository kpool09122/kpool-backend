<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Infrastructure\Factory;

use DateTimeImmutable;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;
use Source\Wiki\Song\Domain\Entity\SongHistory;
use Source\Wiki\Song\Domain\Factory\SongHistoryFactoryInterface;
use Source\Wiki\Song\Domain\ValueObject\SongHistoryIdentifier;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;
use Source\Wiki\Song\Domain\ValueObject\SongName;

readonly class SongHistoryFactory implements SongHistoryFactoryInterface
{
    public function __construct(
        private UuidGeneratorInterface $ulidGenerator,
    ) {
    }

    public function create(
        EditorIdentifier $editorIdentifier,
        ?EditorIdentifier $submitterIdentifier,
        ?SongIdentifier $songIdentifier,
        ?SongIdentifier $draftSongIdentifier,
        ?ApprovalStatus $fromStatus,
        ?ApprovalStatus $toStatus,
        SongName $subjectName,
    ): SongHistory {
        return new SongHistory(
            new SongHistoryIdentifier($this->ulidGenerator->generate()),
            $editorIdentifier,
            $submitterIdentifier,
            $songIdentifier,
            $draftSongIdentifier,
            $fromStatus,
            $toStatus,
            $subjectName,
            new DateTimeImmutable('now'),
        );
    }
}
