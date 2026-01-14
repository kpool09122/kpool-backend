<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\UseCase\Command\CreateSong;

use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Shared\Domain\ValueObject\Language;
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

interface CreateSongInputPort
{
    public function publishedSongIdentifier(): ?SongIdentifier;

    public function language(): Language;

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
