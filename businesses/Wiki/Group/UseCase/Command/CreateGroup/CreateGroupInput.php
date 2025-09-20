<?php

declare(strict_types=1);

namespace Businesses\Wiki\Group\UseCase\Command\CreateGroup;

use Businesses\Shared\ValueObject\Translation;
use Businesses\Wiki\Group\Domain\ValueObject\AgencyIdentifier;
use Businesses\Wiki\Group\Domain\ValueObject\Description;
use Businesses\Wiki\Group\Domain\ValueObject\GroupName;
use Businesses\Wiki\Group\Domain\ValueObject\SongIdentifier;

readonly class CreateGroupInput implements CreateGroupInputPort
{
    /**
     * @param Translation $translation
     * @param GroupName $name
     * @param AgencyIdentifier $agencyIdentifier
     * @param Description $description
     * @param list<SongIdentifier> $songIdentifiers
     * @param string|null $base64EncodedImage
     */
    public function __construct(
        private Translation $translation,
        private GroupName $name,
        private AgencyIdentifier $agencyIdentifier,
        private Description $description,
        private array $songIdentifiers,
        private ?string $base64EncodedImage,
    ) {
    }

    public function translation(): Translation
    {
        return $this->translation;
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
