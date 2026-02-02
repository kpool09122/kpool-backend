<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\UseCase\Command\EditSong;

use Source\Wiki\Shared\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Song\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Song\Domain\ValueObject\Overview;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;
use Source\Wiki\Song\Domain\ValueObject\SongName;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Song\Composer;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Song\Lyricist;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Song\ReleaseDate;

interface EditSongInputPort
{
    public function songIdentifier(): SongIdentifier;

    public function name(): SongName;

    public function agencyIdentifier(): ?AgencyIdentifier;

    public function groupIdentifier(): ?GroupIdentifier;

    public function talentIdentifier(): ?TalentIdentifier;

    public function lyricist(): Lyricist;

    public function composer(): Composer;

    public function releaseDate(): ?ReleaseDate;

    public function overview(): Overview;

    public function principalIdentifier(): PrincipalIdentifier;
}
