<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Domain\ValueObject;

use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\Name;

final readonly class AutoWikiCreationPayload
{
    /**
     * @param WikiIdentifier[] $groupIdentifiers
     * @param WikiIdentifier[] $talentIdentifiers
     */
    public function __construct(
        private Language         $language,
        private ResourceType     $resourceType,
        private Name             $name,
        private ?WikiIdentifier  $agencyIdentifier,
        private array            $groupIdentifiers,
        private array            $talentIdentifiers,
    ) {
    }

    public function language(): Language
    {
        return $this->language;
    }

    public function resourceType(): ResourceType
    {
        return $this->resourceType;
    }

    public function name(): Name
    {
        return $this->name;
    }

    public function agencyIdentifier(): ?WikiIdentifier
    {
        return $this->agencyIdentifier;
    }

    /**
     * @return WikiIdentifier[]
     */
    public function groupIdentifiers(): array
    {
        return $this->groupIdentifiers;
    }

    /**
     * @return WikiIdentifier[]
     */
    public function talentIdentifiers(): array
    {
        return $this->talentIdentifiers;
    }
}
