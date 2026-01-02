<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Domain\Entity;

use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Group\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Group\Domain\ValueObject\Description;
use Source\Wiki\Group\Domain\ValueObject\GroupName;
use Source\Wiki\Shared\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;

class Group
{
    /**
     * @param GroupIdentifier $groupIdentifier
     * @param TranslationSetIdentifier $translationSetIdentifier
     * @param Language $language
     * @param GroupName $name
     * @param string $normalizedName
     * @param AgencyIdentifier|null $agencyIdentifier
     * @param Description $description
     * @param ImagePath|null $imagePath
     * @param Version $version
     */
    public function __construct(
        private readonly GroupIdentifier          $groupIdentifier,
        private readonly TranslationSetIdentifier $translationSetIdentifier,
        private readonly Language                 $language,
        private GroupName                         $name,
        private string                            $normalizedName,
        private ?AgencyIdentifier                 $agencyIdentifier,
        private Description                       $description,
        private ?ImagePath                        $imagePath,
        private Version                           $version,
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

    public function language(): Language
    {
        return $this->language;
    }

    public function name(): GroupName
    {
        return $this->name;
    }

    public function setName(GroupName $name): void
    {
        $this->name = $name;
    }

    public function normalizedName(): string
    {
        return $this->normalizedName;
    }

    public function setNormalizedName(string $normalizedName): void
    {
        $this->normalizedName = $normalizedName;
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

    public function hasSameVersion(Version $version): bool
    {
        return $this->version->value() === $version->value();
    }

    public function isVersionGreaterThan(Version $version): bool
    {
        return $this->version->value() > $version->value();
    }
}
