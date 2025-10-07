<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\UseCase\Command\CreateSong;

use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Shared\Domain\ValueObject\Translation;
use Source\Wiki\Shared\Domain\Entity\Principal;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;
use Source\Wiki\Song\Domain\ValueObject\BelongIdentifier;
use Source\Wiki\Song\Domain\ValueObject\Composer;
use Source\Wiki\Song\Domain\ValueObject\Lyricist;
use Source\Wiki\Song\Domain\ValueObject\Overview;
use Source\Wiki\Song\Domain\ValueObject\ReleaseDate;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;
use Source\Wiki\Song\Domain\ValueObject\SongName;

interface CreateSongInputPort
{
    public function publishedSongIdentifier(): ?SongIdentifier;

    public function editorIdentifier(): EditorIdentifier;

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

    public function principal(): Principal;
}
