<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\DTO;

use InvalidArgumentException;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

final readonly class RelatedWikiContext
{
    public function __construct(public WikiIdentifier $identifier, public string $title, public ResourceType $resourceType, public string $relationType)
    {
        if (trim($title) === '' || mb_strlen($title) > 255 || trim($relationType) === '' || mb_strlen($relationType) > 100
            || ! in_array($resourceType, [ResourceType::AGENCY, ResourceType::GROUP, ResourceType::TALENT, ResourceType::SONG], true)) {
            throw new InvalidArgumentException('Invalid related Wiki search context');
        }
    }

    /** @return array{identifier: string, title: string, resourceType: string, relationType: string} */
    public function toArray(): array
    {
        return ['identifier' => (string) $this->identifier, 'title' => $this->title, 'resourceType' => $this->resourceType->value, 'relationType' => $this->relationType];
    }
}
