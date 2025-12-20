<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Domain\Factory;

use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;
use Source\Wiki\Song\Domain\Entity\SongHistory;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;

interface SongHistoryFactoryInterface
{
    public function create(
        EditorIdentifier $editorIdentifier,
        ?EditorIdentifier $submitterIdentifier,
        ?SongIdentifier $songIdentifier,
        ?SongIdentifier $draftSongIdentifier,
        ?ApprovalStatus $fromStatus,
        ApprovalStatus $toStatus,
    ): SongHistory;
}
