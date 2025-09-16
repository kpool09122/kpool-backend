<?php

namespace Businesses\Wiki\Group\Domain\Entity;

use Businesses\Shared\ValueObject\ImagePath;
use Businesses\Wiki\Group\Domain\ValueObject\AgencyIdentifier;
use Businesses\Wiki\Group\Domain\ValueObject\Description;
use Businesses\Wiki\Group\Domain\ValueObject\GroupIdentifier;
use Businesses\Wiki\Group\Domain\ValueObject\GroupName;
use Businesses\Wiki\Group\Domain\ValueObject\SongIdentifier;

class Group
{
    /**
     * @param GroupIdentifier $groupIdentifier
     * @param GroupName $name
     * @param AgencyIdentifier|null $agencyIdentifier
     * @param Description $description
     * @param list<SongIdentifier> $songIdentifiers
     * @param ImagePath|null $imageLink
     */
    public function __construct(
        private readonly GroupIdentifier $groupIdentifier,
        private GroupName                $name,
        private ?AgencyIdentifier        $agencyIdentifier,
        private Description              $description,
        private array                    $songIdentifiers,
        private ?ImagePath               $imageLink,
    ) {
    }

    public function groupIdentifier(): GroupIdentifier
    {
        return $this->groupIdentifier;
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

    public function setAgencyIdentifier(?AgencyIdentifier $agencyIdentifier): void
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

    public function imageLink(): ?ImagePath
    {
        return $this->imageLink;
    }

    public function setImageLink(?ImagePath $imageLink): void
    {
        $this->imageLink = $imageLink;
    }
}
