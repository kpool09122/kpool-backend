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

readonly class CreateSongInput implements CreateSongInputPort
{
    /**
     * @param SongIdentifier|null $publishedSongIdentifier
     * @param Language $language
     * @param SongName $name
     * @param ?AgencyIdentifier $agencyIdentifier
     * @param ?GroupIdentifier $groupIdentifier
     * @param ?TalentIdentifier $talentIdentifier
     * @param Lyricist $lyricist
     * @param Composer $composer
     * @param ?ReleaseDate $releaseDate
     * @param Overview $overview
     * @param ?string $base64EncodedCoverImage
     * @param ?ExternalContentLink $musicVideoLink
     * @param PrincipalIdentifier $principalIdentifier
     */
    public function __construct(
        private ?SongIdentifier      $publishedSongIdentifier,
        private Language             $language,
        private SongName             $name,
        private ?AgencyIdentifier    $agencyIdentifier,
        private ?GroupIdentifier     $groupIdentifier,
        private ?TalentIdentifier    $talentIdentifier,
        private Lyricist             $lyricist,
        private Composer             $composer,
        private ?ReleaseDate         $releaseDate,
        private Overview             $overview,
        private ?string              $base64EncodedCoverImage,
        private ?ExternalContentLink $musicVideoLink,
        private PrincipalIdentifier  $principalIdentifier,
    ) {
    }

    public function publishedSongIdentifier(): ?SongIdentifier
    {
        return $this->publishedSongIdentifier;
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

    public function groupIdentifier(): ?GroupIdentifier
    {
        return $this->groupIdentifier;
    }

    public function talentIdentifier(): ?TalentIdentifier
    {
        return $this->talentIdentifier;
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

    public function overview(): Overview
    {
        return $this->overview;
    }

    public function base64EncodedCoverImage(): ?string
    {
        return $this->base64EncodedCoverImage;
    }

    public function musicVideoLink(): ?ExternalContentLink
    {
        return $this->musicVideoLink;
    }

    public function principalIdentifier(): PrincipalIdentifier
    {
        return $this->principalIdentifier;
    }
}
