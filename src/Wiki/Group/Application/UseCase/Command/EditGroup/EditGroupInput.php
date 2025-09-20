<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Application\UseCase\Command\EditGroup;

use Source\Wiki\Group\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Group\Domain\ValueObject\Description;
use Source\Wiki\Group\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Group\Domain\ValueObject\GroupName;
use Source\Wiki\Group\Domain\ValueObject\SongIdentifier;

readonly class EditGroupInput implements EditGroupInputPort
{
    /**
     * @param GroupIdentifier $groupIdentifier
     * @param GroupName $name
     * @param AgencyIdentifier $agencyIdentifier
     * @param Description $description
     * @param list<SongIdentifier> $songIdentifiers
     * @param string|null $base64EncodedImage
     */
    public function __construct(
        private GroupIdentifier  $groupIdentifier,
        private GroupName        $name,
        private AgencyIdentifier $agencyIdentifier,
        private Description      $description,
        private array            $songIdentifiers,
        private ?string          $base64EncodedImage,
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

    public function agencyIdentifier(): AgencyIdentifier
    {
        return $this->agencyIdentifier;
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
