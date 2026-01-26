<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Domain\Entity;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Shared\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Talent\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\Birthday;
use Source\Wiki\Talent\Domain\ValueObject\Career;
use Source\Wiki\Talent\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\RealName;
use Source\Wiki\Talent\Domain\ValueObject\TalentName;
use Source\Wiki\Talent\Domain\ValueObject\TalentSnapshotIdentifier;

readonly class TalentSnapshot
{
    /**
     * @param TalentSnapshotIdentifier $snapshotIdentifier
     * @param TalentIdentifier $talentIdentifier
     * @param TranslationSetIdentifier $translationSetIdentifier
     * @param Slug $slug
     * @param Language $language
     * @param TalentName $name
     * @param RealName $realName
     * @param ?AgencyIdentifier $agencyIdentifier
     * @param list<GroupIdentifier> $groupIdentifiers
     * @param Birthday|null $birthday
     * @param Career $career
     * @param Version $version
     * @param DateTimeImmutable $createdAt
     */
    public function __construct(
        private TalentSnapshotIdentifier  $snapshotIdentifier,
        private TalentIdentifier          $talentIdentifier,
        private TranslationSetIdentifier  $translationSetIdentifier,
        private Slug                      $slug,
        private Language                  $language,
        private TalentName                $name,
        private RealName                  $realName,
        private ?AgencyIdentifier         $agencyIdentifier,
        private array                     $groupIdentifiers,
        private ?Birthday                 $birthday,
        private Career                    $career,
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

    public function snapshotIdentifier(): TalentSnapshotIdentifier
    {
        return $this->snapshotIdentifier;
    }

    public function talentIdentifier(): TalentIdentifier
    {
        return $this->talentIdentifier;
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

    public function name(): TalentName
    {
        return $this->name;
    }

    public function realName(): RealName
    {
        return $this->realName;
    }

    public function agencyIdentifier(): ?AgencyIdentifier
    {
        return $this->agencyIdentifier;
    }

    /**
     * @return GroupIdentifier[]
     */
    public function groupIdentifiers(): array
    {
        return $this->groupIdentifiers;
    }

    public function birthday(): ?Birthday
    {
        return $this->birthday;
    }

    public function career(): Career
    {
        return $this->career;
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
