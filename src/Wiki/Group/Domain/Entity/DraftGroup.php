<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Domain\Entity;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Group\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Group\Domain\ValueObject\Description;
use Source\Wiki\Group\Domain\ValueObject\GroupName;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

class DraftGroup
{
    /**
     * @param GroupIdentifier $groupIdentifier
     * @param GroupIdentifier|null $publishedGroupIdentifier
     * @param TranslationSetIdentifier $translationSetIdentifier
     * @param PrincipalIdentifier $editorIdentifier
     * @param Language $language
     * @param GroupName $name
     * @param string $normalizedName
     * @param AgencyIdentifier|null $agencyIdentifier
     * @param Description $description
     * @param ImagePath|null $imagePath
     * @param ApprovalStatus $status
     * @param PrincipalIdentifier|null $mergerIdentifier
     * @param DateTimeImmutable|null $mergedAt
     */
    public function __construct(
        private readonly GroupIdentifier          $groupIdentifier,
        private ?GroupIdentifier                  $publishedGroupIdentifier,
        private readonly TranslationSetIdentifier $translationSetIdentifier,
        private readonly PrincipalIdentifier      $editorIdentifier,
        private readonly Language                 $language,
        private GroupName                         $name,
        private string                            $normalizedName,
        private ?AgencyIdentifier                 $agencyIdentifier,
        private Description                       $description,
        private ?ImagePath                        $imagePath,
        private ApprovalStatus                    $status,
        private ?PrincipalIdentifier              $mergerIdentifier = null,
        private ?DateTimeImmutable                $mergedAt = null,
    ) {
    }

    public function groupIdentifier(): GroupIdentifier
    {
        return $this->groupIdentifier;
    }

    public function publishedGroupIdentifier(): ?GroupIdentifier
    {
        return $this->publishedGroupIdentifier;
    }

    public function setPublishedGroupIdentifier(GroupIdentifier $groupIdentifier): void
    {
        $this->publishedGroupIdentifier = $groupIdentifier;
    }

    public function translationSetIdentifier(): TranslationSetIdentifier
    {
        return $this->translationSetIdentifier;
    }

    public function editorIdentifier(): PrincipalIdentifier
    {
        return $this->editorIdentifier;
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

    public function status(): ApprovalStatus
    {
        return $this->status;
    }

    public function setStatus(ApprovalStatus $status): void
    {
        $this->status = $status;
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
}
