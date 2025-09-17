<?php

declare(strict_types=1);

namespace Businesses\Wiki\Song\UseCase\Command\CreateSong;

use Businesses\Shared\ValueObject\ExternalContentLink;
use Businesses\Shared\ValueObject\Translation;
use Businesses\Wiki\Song\Domain\ValueObject\BelongIdentifier;
use Businesses\Wiki\Song\Domain\ValueObject\Composer;
use Businesses\Wiki\Song\Domain\ValueObject\Lyricist;
use Businesses\Wiki\Song\Domain\ValueObject\Overview;
use Businesses\Wiki\Song\Domain\ValueObject\ReleaseDate;
use Businesses\Wiki\Song\Domain\ValueObject\SongName;

interface CreateSongInputPort
{
    public function translation(): Translation;

    public function name(): SongName;

    /**
     * @return list<BelongIdentifier>
     */
    public function belongIdentifiers(): array;

    public function lyricist(): Lyricist;

    public function composer(): Composer;

    public function releaseDate(): ?ReleaseDate;

    public function overview(): Overview;

    public function base64EncodedCoverImage(): ?string;

    public function musicVideoLink(): ?ExternalContentLink;
}
