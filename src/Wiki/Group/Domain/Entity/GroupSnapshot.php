<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Domain\Entity;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Group\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Group\Domain\ValueObject\Description;
use Source\Wiki\Group\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Group\Domain\ValueObject\GroupName;
use Source\Wiki\Group\Domain\ValueObject\GroupSnapshotIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;

readonly class GroupSnapshot
{
    /**
     * @param GroupSnapshotIdentifier $snapshotIdentifier
     * @param GroupIdentifier $groupIdentifier
     * @param TranslationSetIdentifier $translationSetIdentifier
     * @param Language $language
     * @param GroupName $name
     * @param string $normalizedName
     * @param AgencyIdentifier|null $agencyIdentifier
     * @param Description $description
     * @param ImagePath|null $imagePath
     * @param Version $version
     * @param DateTimeImmutable $createdAt
     */
    public function __construct(
        private GroupSnapshotIdentifier  $snapshotIdentifier,
        private GroupIdentifier          $groupIdentifier,
        private TranslationSetIdentifier $translationSetIdentifier,
        private Language                 $language,
        private GroupName                $name,
        private string                   $normalizedName,
        private ?AgencyIdentifier        $agencyIdentifier,
        private Description              $description,
        private ?ImagePath               $imagePath,
        private Version                  $version,
        private DateTimeImmutable        $createdAt,
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

    public function imagePath(): ?ImagePath
    {
        return $this->imagePath;
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
