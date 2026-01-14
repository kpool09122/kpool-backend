<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\UseCase\Command\EditSong;

use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Wiki\Shared\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Song\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Song\Domain\ValueObject\Composer;
use Source\Wiki\Song\Domain\ValueObject\Lyricist;
use Source\Wiki\Song\Domain\ValueObject\Overview;
use Source\Wiki\Song\Domain\ValueObject\ReleaseDate;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;
use Source\Wiki\Song\Domain\ValueObject\SongName;

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

    public function musicVideoLink(): ?ExternalContentLink;

    public function principalIdentifier(): PrincipalIdentifier;
}
