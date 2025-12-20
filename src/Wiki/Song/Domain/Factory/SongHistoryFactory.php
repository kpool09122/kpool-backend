<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Domain\Factory;

use DateTimeImmutable;
use Source\Shared\Application\Service\Ulid\UlidGeneratorInterface;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;
use Source\Wiki\Song\Domain\Entity\SongHistory;
use Source\Wiki\Song\Domain\ValueObject\SongHistoryIdentifier;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;

readonly class SongHistoryFactory implements SongHistoryFactoryInterface
{
    public function __construct(
        private UlidGeneratorInterface $ulidGenerator,
    ) {
    }

    public function create(
        EditorIdentifier $editorIdentifier,
        ?EditorIdentifier $submitterIdentifier,
        ?SongIdentifier $songIdentifier,
        ?SongIdentifier $draftSongIdentifier,
        ?ApprovalStatus $fromStatus,
        ApprovalStatus $toStatus,
    ): SongHistory {
        return new SongHistory(
            new SongHistoryIdentifier($this->ulidGenerator->generate()),
            $editorIdentifier,
            $submitterIdentifier,
            $songIdentifier,
            $draftSongIdentifier,
            $fromStatus,
            $toStatus,
            new DateTimeImmutable('now'),
        );
    }
}
