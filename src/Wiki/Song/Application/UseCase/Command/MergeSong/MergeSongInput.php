<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\UseCase\Command\MergeSong;

use DateTimeImmutable;
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
     * @param PrincipalIdentifier $principalIdentifier
     * @param DateTimeImmutable $mergedAt
     */
    public function __construct(
        private SongIdentifier      $songIdentifier,
        private SongName            $name,
        private ?AgencyIdentifier   $agencyIdentifier,
        private ?GroupIdentifier    $groupIdentifier,
        private ?TalentIdentifier   $talentIdentifier,
        private Lyricist            $lyricist,
        private Composer            $composer,
        private ?ReleaseDate        $releaseDate,
        private Overview            $overView,
        private PrincipalIdentifier $principalIdentifier,
        private DateTimeImmutable   $mergedAt,
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

    public function principalIdentifier(): PrincipalIdentifier
    {
        return $this->principalIdentifier;
    }

    public function mergedAt(): DateTimeImmutable
    {
        return $this->mergedAt;
    }
}
