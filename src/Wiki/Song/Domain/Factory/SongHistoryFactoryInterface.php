<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Domain\Factory;

use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Song\Domain\Entity\SongHistory;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;
use Source\Wiki\Song\Domain\ValueObject\SongName;

interface SongHistoryFactoryInterface
{
    public function create(
        PrincipalIdentifier $editorIdentifier,
        ?PrincipalIdentifier $submitterIdentifier,
        ?SongIdentifier $songIdentifier,
        ?SongIdentifier $draftSongIdentifier,
        ?ApprovalStatus $fromStatus,
        ?ApprovalStatus $toStatus,
        SongName $subjectName,
    ): SongHistory;
}
