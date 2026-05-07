<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query\ListWikis;

readonly class ListWikisInput implements ListWikisInputPort
{
    public function __construct(
        private ?int $perPage = null,
        private ?string $resourceType = null,
        private ?string $keyword = null,
        private ?string $sort = null,
        private ?string $order = null,
    ) {
    }

    public function perPage(): int
    {
        return $this->perPage ?? 10;
    }

    public function resourceType(): ?string
    {
        return $this->resourceType;
    }

    public function keyword(): ?string
    {
        return $this->keyword;
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
