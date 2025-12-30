<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Domain\Entity;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Agency\Domain\ValueObject\AgencyName;
use Source\Wiki\Agency\Domain\ValueObject\AgencySnapshotIdentifier;
use Source\Wiki\Agency\Domain\ValueObject\CEO;
use Source\Wiki\Agency\Domain\ValueObject\Description;
use Source\Wiki\Agency\Domain\ValueObject\FoundedIn;
use Source\Wiki\Shared\Domain\ValueObject\Version;

readonly class AgencySnapshot
{
    public function __construct(
        private AgencySnapshotIdentifier  $snapshotIdentifier,
        private AgencyIdentifier          $agencyIdentifier,
        private TranslationSetIdentifier  $translationSetIdentifier,
        private Language                  $language,
        private AgencyName                $name,
        private string                    $normalizedName,
        private CEO                       $CEO,
        private string                    $normalizedCEO,
        private ?FoundedIn                $foundedIn,
        private Description               $description,
        private Version                   $version,
        private DateTimeImmutable         $createdAt,
    ) {
    }

    public function snapshotIdentifier(): AgencySnapshotIdentifier
    {
        return $this->snapshotIdentifier;
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

    public function normalizedName(): string
    {
        return $this->normalizedName;
    }

    public function CEO(): CEO
    {
        return $this->CEO;
    }

    public function normalizedCEO(): string
    {
        return $this->normalizedCEO;
    }

    public function foundedIn(): ?FoundedIn
    {
        return $this->foundedIn;
    }

    public function description(): Description
    {
        return $this->description;
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
