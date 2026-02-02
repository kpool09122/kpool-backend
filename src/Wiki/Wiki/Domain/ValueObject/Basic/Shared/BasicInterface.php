<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared;

use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

interface BasicInterface
{
    public function supportsResourceType(ResourceType $resourceType): bool;

    public function getBasicType(): string;

    public function name(): Name;

    public function normalizedName(): string;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self;
}
