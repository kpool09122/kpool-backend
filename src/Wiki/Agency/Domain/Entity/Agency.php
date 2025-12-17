<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Domain\Entity;

use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Agency\Domain\ValueObject\AgencyName;
use Source\Wiki\Agency\Domain\ValueObject\CEO;
use Source\Wiki\Agency\Domain\ValueObject\Description;
use Source\Wiki\Agency\Domain\ValueObject\FoundedIn;
use Source\Wiki\Shared\Domain\ValueObject\Version;

class Agency
{
    public function __construct(
        private readonly AgencyIdentifier         $agencyIdentifier,
        private readonly TranslationSetIdentifier $translationSetIdentifier,
        private readonly Language                 $language,
        private AgencyName                        $name,
        private string                            $normalizedName,
        private CEO                               $CEO,
        private string                            $normalizedCEO,
        private ?FoundedIn                        $foundedIn,
        private Description                       $description,
        private Version                           $version,
    ) {
    }

    public function agencyIdentifier(): AgencyIdentifier
    {
        return $this->agencyIdentifier;
    }

    public function translationSetIdentifier(): TranslationSetIdentifier
    {
        return $this->translationSetIdentifier;
    }

    public function language(): Language
    {
        return $this->language;
    }

    public function name(): AgencyName
    {
        return $this->name;
    }

    public function setName(AgencyName $name): void
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

    public function CEO(): ?CEO
    {
        return $this->CEO;
    }

    public function setCEO(CEO $CEO): void
    {
        $this->CEO = $CEO;
    }

    public function normalizedCEO(): string
    {
        return $this->normalizedCEO;
    }

    public function setNormalizedCEO(string $normalizedCEO): void
    {
        $this->normalizedCEO = $normalizedCEO;
    }

    public function foundedIn(): ?FoundedIn
    {
        return $this->foundedIn;
    }

    public function setFoundedIn(FoundedIn $foundedIn): void
    {
        $this->foundedIn = $foundedIn;
    }

    public function description(): Description
    {
        return $this->description;
    }

    public function setDescription(Description $description): void
    {
        $this->description = $description;
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
