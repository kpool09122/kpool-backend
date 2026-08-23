<?php

declare(strict_types=1);

namespace Source\Wiki\OfficialCertification\Application\Service;

use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

readonly class SyncableOwnedWikiResource
{
    public function __construct(
        private ResourceType $resourceType,
        private TranslationSetIdentifier $translationSetIdentifier,
    ) {
    }

    public function resourceType(): ResourceType
    {
        return $this->resourceType;
    }

    public function translationSetIdentifier(): TranslationSetIdentifier
    {
        return $this->translationSetIdentifier;
    }

    public function key(): string
    {
        return $this->resourceType->value . ':' . (string) $this->translationSetIdentifier;
    }

    /** @return array{resourceType: string, translationSetIdentifier: string} */
    public function toArray(): array
    {
        return [
            'resourceType' => $this->resourceType->value,
            'translationSetIdentifier' => (string) $this->translationSetIdentifier,
        ];
    }
}
