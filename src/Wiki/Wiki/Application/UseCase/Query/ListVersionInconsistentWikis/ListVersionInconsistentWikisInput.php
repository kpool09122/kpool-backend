<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query\ListVersionInconsistentWikis;

use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

readonly class ListVersionInconsistentWikisInput implements ListVersionInconsistentWikisInputPort
{
    public function __construct(
        private ?int $perPage = null,
        private ?ResourceType $resourceType = null,
        private ?string $sort = null,
        private ?string $order = null,
    ) {
    }

    public function perPage(): int
    {
        return $this->perPage ?? 10;
    }

    public function resourceType(): ?ResourceType
    {
        return $this->resourceType;
    }

    public function sort(): string
    {
        return $this->sort ?? 'updatedAt';
    }

    public function order(): string
    {
        return $this->order ?? 'desc';
    }
}
