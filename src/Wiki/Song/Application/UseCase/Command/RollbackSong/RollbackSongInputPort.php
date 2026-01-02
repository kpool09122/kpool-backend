<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\UseCase\Command\RollbackSong;

use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;

interface RollbackSongInputPort
{
    public function principalIdentifier(): PrincipalIdentifier;

    public function songIdentifier(): SongIdentifier;

    public function targetVersion(): Version;
}
