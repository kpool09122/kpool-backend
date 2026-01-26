<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Domain\Entity;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Group\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Group\Domain\ValueObject\Description;
use Source\Wiki\Group\Domain\ValueObject\GroupName;
use Source\Wiki\Group\Domain\ValueObject\GroupSnapshotIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Shared\Domain\ValueObject\Version;

readonly class GroupSnapshot
{
    /**
     * @param GroupSnapshotIdentifier $snapshotIdentifier
     * @param GroupIdentifier $groupIdentifier
     * @param TranslationSetIdentifier $translationSetIdentifier
     * @param Slug $slug
     * @param Language $language
     * @param GroupName $name
     * @param string $normalizedName
     * @param AgencyIdentifier|null $agencyIdentifier
     * @param Description $description
     * @param Version $version
     * @param DateTimeImmutable $createdAt
     */
    public function __construct(
        private GroupSnapshotIdentifier  $snapshotIdentifier,
        private GroupIdentifier          $groupIdentifier,
        private TranslationSetIdentifier $translationSetIdentifier,
        private Slug                     $slug,
        private Language                 $language,
        private GroupName                $name,
        private string                   $normalizedName,
        private ?AgencyIdentifier        $agencyIdentifier,
        private Description              $description,
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

    public function snapshotIdentifier(): GroupSnapshotIdentifier
    {
        return $this->snapshotIdentifier;
    }

    public function groupIdentifier(): GroupIdentifier
    {
        return $this->groupIdentifier;
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

    public function name(): GroupName
    {
        return $this->name;
    }

    public function normalizedName(): string
    {
        return $this->normalizedName;
    }

    public function agencyIdentifier(): ?AgencyIdentifier
    {
        return $this->agencyIdentifier;
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
