<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Domain\Entity;

use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Translation;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Group\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Group\Domain\ValueObject\Description;
use Source\Wiki\Group\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Group\Domain\ValueObject\GroupName;
use Source\Wiki\Group\Domain\ValueObject\SongIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;

class Group
{
    /**
     * @param GroupIdentifier $groupIdentifier
     * @param TranslationSetIdentifier $translationSetIdentifier
     * @param Translation $translation
     * @param GroupName $name
     * @param AgencyIdentifier|null $agencyIdentifier
     * @param Description $description
     * @param list<SongIdentifier> $songIdentifiers
     * @param ImagePath|null $imagePath
     * @param Version $version
     */
    public function __construct(
        private readonly GroupIdentifier          $groupIdentifier,
        private readonly TranslationSetIdentifier $translationSetIdentifier,
        private readonly Translation              $translation,
        private GroupName                         $name,
        private ?AgencyIdentifier                 $agencyIdentifier,
        private Description                       $description,
        private array                             $songIdentifiers,
        private ?ImagePath                        $imagePath,
        private Version $version,
    ) {
    }

    public function groupIdentifier(): GroupIdentifier
    {
        return $this->groupIdentifier;
    }

    public function translationSetIdentifier(): TranslationSetIdentifier
    {
        return $this->translationSetIdentifier;
    }

    public function translation(): Translation
    {
        return $this->translation;
    }

    public function name(): GroupName
    {
        return $this->name;
    }

    public function setName(GroupName $name): void
    {
        $this->name = $name;
    }

    public function agencyIdentifier(): ?AgencyIdentifier
    {
        return $this->agencyIdentifier;
    }

    public function setAgencyIdentifier(AgencyIdentifier $agencyIdentifier): void
    {
        $this->agencyIdentifier = $agencyIdentifier;
    }

    public function description(): Description
    {
        return $this->description;
    }

    public function setDescription(Description $description): void
    {
        $this->description = $description;
    }

    /**
     * @return list<SongIdentifier>
     */
    public function songIdentifiers(): array
    {
        return $this->songIdentifiers;
    }

    /**
     * @param list<SongIdentifier> $songIdentifiers
     * @return void
     */
    public function setSongIdentifiers(array $songIdentifiers): void
    {
        $this->songIdentifiers = $songIdentifiers;
    }

    public function imagePath(): ?ImagePath
    {
        return $this->imagePath;
    }

    public function setImagePath(?ImagePath $imagePath): void
    {
        $this->imagePath = $imagePath;
    }

    public function version(): Version
    {
        return $this->version;
    }

    public function updateVersion(): void
    {
        $this->version = Version::nextVersion($this->version);
    }
}
