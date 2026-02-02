<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Domain\Entity;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Shared\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\Career;
use Source\Wiki\Talent\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\TalentName;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Talent\Birthday;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Talent\RealName;

class DraftTalent
{
    /**
     * @param TalentIdentifier $talentIdentifier
     * @param TalentIdentifier|null $publishedTalentIdentifier
     * @param TranslationSetIdentifier $translationSetIdentifier
     * @param Slug $slug
     * @param PrincipalIdentifier|null $editorIdentifier
     * @param Language $language
     * @param TalentName $name
     * @param RealName $realName
     * @param ?AgencyIdentifier $agencyIdentifier
     * @param GroupIdentifier[] $groupIdentifiers
     * @param Birthday|null $birthday
     * @param Career $career
     * @param ApprovalStatus $status
     * @param PrincipalIdentifier|null $mergerIdentifier
     * @param DateTimeImmutable|null $mergedAt
     */
    public function __construct(
        private readonly TalentIdentifier         $talentIdentifier,
        private ?TalentIdentifier                 $publishedTalentIdentifier,
        private readonly TranslationSetIdentifier $translationSetIdentifier,
        private readonly Slug                     $slug,
        private readonly ?PrincipalIdentifier     $editorIdentifier,
        private readonly Language                 $language,
        private TalentName                        $name,
        private RealName                          $realName,
        private ?AgencyIdentifier                 $agencyIdentifier,
        private array                             $groupIdentifiers,
        private ?Birthday                         $birthday,
        private Career                            $career,
        private ApprovalStatus                    $status,
        private ?PrincipalIdentifier              $approverIdentifier = null,
        private ?PrincipalIdentifier              $mergerIdentifier = null,
        private ?DateTimeImmutable                $mergedAt = null,
        private ?PrincipalIdentifier              $sourceEditorIdentifier = null,
        private ?DateTimeImmutable                $translatedAt = null,
        private ?DateTimeImmutable                $approvedAt = null,
    ) {
    }

    public function talentIdentifier(): TalentIdentifier
    {
        return $this->talentIdentifier;
    }

    public function publishedTalentIdentifier(): ?TalentIdentifier
    {
        return $this->publishedTalentIdentifier;
    }

    public function setPublishedTalentIdentifier(TalentIdentifier $talentIdentifier): void
    {
        $this->publishedTalentIdentifier = $talentIdentifier;
    }

    public function translationSetIdentifier(): TranslationSetIdentifier
    {
        return $this->translationSetIdentifier;
    }

    public function slug(): Slug
    {
        return $this->slug;
    }

    public function editorIdentifier(): ?PrincipalIdentifier
    {
        return $this->editorIdentifier;
    }

    public function language(): Language
    {
        return $this->language;
    }

    public function name(): TalentName
    {
        return $this->name;
    }

    public function setName(TalentName $name): void
    {
        $this->name = $name;
    }

    public function realName(): RealName
    {
        return $this->realName;
    }

    public function setRealName(RealName $realName): void
    {
        $this->realName = $realName;
    }

    public function agencyIdentifier(): ?AgencyIdentifier
    {
        return $this->agencyIdentifier;
    }

    public function setAgencyIdentifier(AgencyIdentifier $agencyIdentifier): void
    {
        $this->agencyIdentifier = $agencyIdentifier;
    }

    /**
     * @return GroupIdentifier[]
     */
    public function groupIdentifiers(): array
    {
        return $this->groupIdentifiers;
    }

    /**
     * @param GroupIdentifier[] $groupIdentifiers
     * @return void
     */
    public function setGroupIdentifiers(array $groupIdentifiers): void
    {
        $this->groupIdentifiers = $groupIdentifiers;
    }

    public function birthday(): ?Birthday
    {
        return $this->birthday;
    }

    public function setBirthday(?Birthday $birthday): void
    {
        $this->birthday = $birthday;
    }

    public function career(): Career
    {
        return $this->career;
    }

    public function setCareer(Career $career): void
    {
        $this->career = $career;
    }

    public function status(): ApprovalStatus
    {
        return $this->status;
    }

    public function setStatus(ApprovalStatus $status): void
    {
        $this->status = $status;
    }

    public function approverIdentifier(): ?PrincipalIdentifier
    {
        return $this->approverIdentifier;
    }

    public function setApproverIdentifier(?PrincipalIdentifier $approverIdentifier): void
    {
        $this->approverIdentifier = $approverIdentifier;
    }

    public function mergerIdentifier(): ?PrincipalIdentifier
    {
        return $this->mergerIdentifier;
    }

    public function setMergerIdentifier(?PrincipalIdentifier $mergerIdentifier): void
    {
        $this->mergerIdentifier = $mergerIdentifier;
    }

    public function mergedAt(): ?DateTimeImmutable
    {
        return $this->mergedAt;
    }

    public function setMergedAt(?DateTimeImmutable $mergedAt): void
    {
        $this->mergedAt = $mergedAt;
    }

    public function sourceEditorIdentifier(): ?PrincipalIdentifier
    {
        return $this->sourceEditorIdentifier;
    }

    public function setSourceEditorIdentifier(?PrincipalIdentifier $sourceEditorIdentifier): void
    {
        $this->sourceEditorIdentifier = $sourceEditorIdentifier;
    }

    public function translatedAt(): ?DateTimeImmutable
    {
        return $this->translatedAt;
    }

    public function setTranslatedAt(?DateTimeImmutable $translatedAt): void
    {
        $this->translatedAt = $translatedAt;
    }

    public function approvedAt(): ?DateTimeImmutable
    {
        return $this->approvedAt;
    }

    public function setApprovedAt(?DateTimeImmutable $approvedAt): void
    {
        $this->approvedAt = $approvedAt;
    }
}
