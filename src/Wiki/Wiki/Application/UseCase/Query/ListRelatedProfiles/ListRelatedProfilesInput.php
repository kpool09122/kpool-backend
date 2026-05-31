<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query\ListRelatedProfiles;

use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\Slug;

readonly class ListRelatedProfilesInput implements ListRelatedProfilesInputPort
{
    public function __construct(
        private Slug $slug,
        private Language $language,
        private ResourceType $resourceType,
    ) {
        if ($resourceType === ResourceType::IMAGE) {
            throw new InvalidArgumentException('Unsupported related profile resource type.');
        }
    }

    public function slug(): Slug
    {
        return $this->slug;
    }

    public function language(): Language
    {
        return $this->language;
    }

    public function resourceType(): ResourceType
    {
        return $this->resourceType;
    }
}
