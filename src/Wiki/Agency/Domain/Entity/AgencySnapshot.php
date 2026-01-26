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
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Shared\Domain\ValueObject\Version;

readonly class AgencySnapshot
{
    public function __construct(
        private AgencySnapshotIdentifier  $snapshotIdentifier,
        private AgencyIdentifier          $agencyIdentifier,
        private TranslationSetIdentifier  $translationSetIdentifier,
        private Slug                      $slug,
        private Language                  $language,
        private AgencyName                $name,
        private string                    $normalizedName,
        private CEO                       $CEO,
        private string                    $normalizedCEO,
        private ?FoundedIn                $foundedIn,
        private Description               $description,
        private Version                   $version,
        private DateTimeImmutable         $createdAt,
        private ?PrincipalIdentifier      $editorIdentifier = null,
        private ?PrincipalIdentifier      $approverIdentifier = null,
        private ?PrincipalIdentifier      $mergerIdentifier = null,
        private ?DateTimeImmutable        $mergedAt = null,
        private ?PrincipalIdentifier      $sourceEditorIdentifier = null,
        private ?DateTimeImmutable        $translatedAt = null,
        private ?DateTimeImmutable        $approvedAt = null,
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

    public function slug(): Slug
    {
        return $this->slug;
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
