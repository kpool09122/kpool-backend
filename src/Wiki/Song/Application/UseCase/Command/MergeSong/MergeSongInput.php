<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\UseCase\Command\MergeSong;

use DateTimeImmutable;
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

readonly class MergeSongInput implements MergeSongInputPort
{
    /**
     * @param SongIdentifier $songIdentifier
     * @param SongName $name
     * @param AgencyIdentifier|null $agencyIdentifier
     * @param GroupIdentifier|null $groupIdentifier
     * @param TalentIdentifier|null $talentIdentifier
     * @param Lyricist $lyricist
     * @param Composer $composer
     * @param ReleaseDate|null $releaseDate
     * @param Overview $overView
     * @param ExternalContentLink|null $musicVideoLink
     * @param PrincipalIdentifier $principalIdentifier
     * @param DateTimeImmutable $mergedAt
     */
    public function __construct(
        private SongIdentifier       $songIdentifier,
        private SongName             $name,
        private ?AgencyIdentifier    $agencyIdentifier,
        private ?GroupIdentifier     $groupIdentifier,
        private ?TalentIdentifier    $talentIdentifier,
        private Lyricist             $lyricist,
        private Composer             $composer,
        private ?ReleaseDate         $releaseDate,
        private Overview             $overView,
        private ?ExternalContentLink $musicVideoLink,
        private PrincipalIdentifier  $principalIdentifier,
        private DateTimeImmutable    $mergedAt,
    ) {
    }

    public function songIdentifier(): SongIdentifier
    {
        return $this->songIdentifier;
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

    public function overView(): Overview
    {
        return $this->overView;
    }

    public function musicVideoLink(): ?ExternalContentLink
    {
        return $this->musicVideoLink;
    }

    public function principalIdentifier(): PrincipalIdentifier
    {
        return $this->principalIdentifier;
    }

    public function mergedAt(): DateTimeImmutable
    {
        return $this->mergedAt;
    }
}
