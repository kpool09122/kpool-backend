<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Domain\Entity;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Song\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Song\Domain\ValueObject\BelongIdentifier;
use Source\Wiki\Song\Domain\ValueObject\Composer;
use Source\Wiki\Song\Domain\ValueObject\Lyricist;
use Source\Wiki\Song\Domain\ValueObject\Overview;
use Source\Wiki\Song\Domain\ValueObject\ReleaseDate;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;
use Source\Wiki\Song\Domain\ValueObject\SongName;
use Source\Wiki\Song\Domain\ValueObject\SongSnapshotIdentifier;

readonly class SongSnapshot
{
    /**
     * @param SongSnapshotIdentifier $snapshotIdentifier
     * @param SongIdentifier $songIdentifier
     * @param TranslationSetIdentifier $translationSetIdentifier
     * @param Language $language
     * @param SongName $name
     * @param ?AgencyIdentifier $agencyIdentifier
     * @param list<BelongIdentifier> $belongIdentifiers
     * @param Lyricist $lyricist
     * @param Composer $composer
     * @param ReleaseDate|null $releaseDate
     * @param Overview $overView
     * @param ImagePath|null $coverImagePath
     * @param ?ExternalContentLink $musicVideoLink
     * @param Version $version
     * @param DateTimeImmutable $createdAt
     */
    public function __construct(
        private SongSnapshotIdentifier   $snapshotIdentifier,
        private SongIdentifier           $songIdentifier,
        private TranslationSetIdentifier $translationSetIdentifier,
        private Language                 $language,
        private SongName                 $name,
        private ?AgencyIdentifier        $agencyIdentifier,
        private array                    $belongIdentifiers,
        private Lyricist                 $lyricist,
        private Composer                 $composer,
        private ?ReleaseDate             $releaseDate,
        private Overview                 $overView,
        private ?ImagePath               $coverImagePath,
        private ?ExternalContentLink     $musicVideoLink,
        private Version                  $version,
        private DateTimeImmutable        $createdAt,
    ) {
    }

    public function snapshotIdentifier(): SongSnapshotIdentifier
    {
        return $this->snapshotIdentifier;
    }

    public function songIdentifier(): SongIdentifier
    {
        return $this->songIdentifier;
    }

    public function translationSetIdentifier(): TranslationSetIdentifier
    {
        return $this->translationSetIdentifier;
    }

    public function language(): Language
    {
        return $this->language;
    }

    public function name(): SongName
    {
        return $this->name;
    }

    public function agencyIdentifier(): ?AgencyIdentifier
    {
        return $this->agencyIdentifier;
    }

    /**
     * @return BelongIdentifier[]
     */
    public function belongIdentifiers(): array
    {
        return $this->belongIdentifiers;
    }

    public function lyricist(): Lyricist
    {
        return $this->lyricist;
    }

    public function composer(): Composer
    {
        return $this->composer;
    }

    public function releaseDate(): ?ReleaseDate
    {
        return $this->releaseDate;
    }

    public function overView(): Overview
    {
        return $this->overView;
    }

    public function coverImagePath(): ?ImagePath
    {
        return $this->coverImagePath;
    }

    public function musicVideoLink(): ?ExternalContentLink
    {
        return $this->musicVideoLink;
    }

    public function version(): Version
    {
        return $this->version;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
