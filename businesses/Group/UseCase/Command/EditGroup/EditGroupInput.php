<?php

namespace Businesses\Group\UseCase\Command\EditGroup;

use Businesses\Group\Domain\ValueObject\CompanyIdentifier;
use Businesses\Group\Domain\ValueObject\Description;
use Businesses\Group\Domain\ValueObject\GroupIdentifier;
use Businesses\Group\Domain\ValueObject\GroupName;
use Businesses\Group\Domain\ValueObject\SongIdentifier;

readonly class EditGroupInput implements EditGroupInputPort
{
    /**
     * @param GroupIdentifier $groupIdentifier
     * @param GroupName $name
     * @param CompanyIdentifier $companyIdentifier
     * @param Description $description
     * @param list<SongIdentifier> $songIdentifiers
     * @param string|null $base64EncodedImage
     */
    public function __construct(
        private GroupIdentifier $groupIdentifier,
        private GroupName $name,
        private CompanyIdentifier $companyIdentifier,
        private Description $description,
        private array $songIdentifiers,
        private ?string $base64EncodedImage,
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

    public function companyIdentifier(): CompanyIdentifier
    {
        return $this->companyIdentifier;
    }

    public function description(): Description
    {
        return $this->description;
    }

    /**
     * @return list<SongIdentifier>
     */
    public function songIdentifiers(): array
    {
        return $this->songIdentifiers;
    }

    public function base64EncodedImage(): ?string
    {
        return $this->base64EncodedImage;
    }
}
