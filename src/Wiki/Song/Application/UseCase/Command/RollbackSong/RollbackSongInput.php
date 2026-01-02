<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\UseCase\Command\RollbackSong;

use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;

readonly class RollbackSongInput implements RollbackSongInputPort
{
    public function __construct(
        private PrincipalIdentifier $principalIdentifier,
        private SongIdentifier $songIdentifier,
        private Version $targetVersion,
    ) {
    }

    public function principalIdentifier(): PrincipalIdentifier
    {
        return $this->principalIdentifier;
    }

    public function songIdentifier(): SongIdentifier
    {
        return $this->songIdentifier;
    }

    public function targetVersion(): Version
    {
        return $this->targetVersion;
    }
}
