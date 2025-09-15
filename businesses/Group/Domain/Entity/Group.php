<?php

namespace Businesses\Group\Domain\Entity;

use Businesses\Group\Domain\ValueObject\CompanyIdentifier;
use Businesses\Group\Domain\ValueObject\Description;
use Businesses\Group\Domain\ValueObject\GroupIdentifier;
use Businesses\Group\Domain\ValueObject\GroupName;
use Businesses\Group\Domain\ValueObject\SongIdentifier;
use Businesses\Shared\ValueObject\ImageLink;

class Group
{
    /**
     * @param GroupIdentifier $groupIdentifier
     * @param GroupName $name
     * @param CompanyIdentifier|null $companyIdentifier
     * @param Description $description
     * @param list<SongIdentifier> $songIdentifiers
     * @param ImageLink|null $imageLink
     */
    public function __construct(
        private readonly GroupIdentifier $groupIdentifier,
        private GroupName $name,
        private ?CompanyIdentifier $companyIdentifier,
        private Description $description,
        private array $songIdentifiers,
        private ?ImageLink $imageLink,
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

    public function companyIdentifier(): ?CompanyIdentifier
    {
        return $this->companyIdentifier;
    }

    public function setCompanyIdentifier(?CompanyIdentifier $companyIdentifier): void
    {
        $this->companyIdentifier = $companyIdentifier;
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

    public function imageLink(): ?ImageLink
    {
        return $this->imageLink;
    }

    public function setImageLink(?ImageLink $imageLink): void
    {
        $this->imageLink = $imageLink;
    }
}
