<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Domain\Entity;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Shared\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Song\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Song\Domain\ValueObject\Overview;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;
use Source\Wiki\Song\Domain\ValueObject\SongName;
use Source\Wiki\Song\Domain\ValueObject\SongSnapshotIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Song\Composer;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Song\Lyricist;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Song\ReleaseDate;

readonly class SongSnapshot
{
    /**
     * @param SongSnapshotIdentifier $snapshotIdentifier
     * @param SongIdentifier $songIdentifier
     * @param TranslationSetIdentifier $translationSetIdentifier
     * @param Slug $slug
     * @param Language $language
     * @param SongName $name
     * @param ?AgencyIdentifier $agencyIdentifier
     * @param ?GroupIdentifier $groupIdentifier
     * @param ?TalentIdentifier $talentIdentifier
     * @param Lyricist $lyricist
     * @param Composer $composer
     * @param ReleaseDate|null $releaseDate
     * @param Overview $overView
     * @param Version $version
     * @param DateTimeImmutable $createdAt
     */
    public function __construct(
        private SongSnapshotIdentifier   $snapshotIdentifier,
        private SongIdentifier           $songIdentifier,
        private TranslationSetIdentifier $translationSetIdentifier,
        private Slug                     $slug,
        private Language                 $language,
        private SongName                 $name,
        private ?AgencyIdentifier        $agencyIdentifier,
        private ?GroupIdentifier         $groupIdentifier,
        private ?TalentIdentifier        $talentIdentifier,
        private Lyricist                 $lyricist,
        private Composer                 $composer,
        private ?ReleaseDate             $releaseDate,
        private Overview                 $overView,
        private Version                  $version,
        private DateTimeImmutable        $createdAt,
        private ?PrincipalIdentifier     $editorIdentifier = null,
        private ?PrincipalIdentifier     $approverIdentifier = null,
        private ?PrincipalIdentifier     $mergerIdentifier = null,
        private ?DateTimeImmutable       $mergedAt = null,
        private ?PrincipalIdentifier     $sourceEditorIdentifier = null,
        private ?DateTimeImmutable       $translatedAt = null,
        private ?DateTimeImmutable       $approvedAt = null,
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

    public function slug(): Slug
    {
        return $this->slug;
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

    public function overView(): Overview
    {
        return $this->overView;
    }

    public function version(): Version
    {
        return $this->version;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function editorIdentifier(): ?PrincipalIdentifier
    {
        return $this->editorIdentifier;
    }

    public function approverIdentifier(): ?PrincipalIdentifier
    {
        return $this->approverIdentifier;
    }

    public function mergerIdentifier(): ?PrincipalIdentifier
    {
        return $this->mergerIdentifier;
    }

    public function mergedAt(): ?DateTimeImmutable
    {
        return $this->mergedAt;
    }

    public function sourceEditorIdentifier(): ?PrincipalIdentifier
    {
        return $this->sourceEditorIdentifier;
    }

    public function translatedAt(): ?DateTimeImmutable
    {
        return $this->translatedAt;
    }

    public function approvedAt(): ?DateTimeImmutable
    {
        return $this->approvedAt;
    }
}
