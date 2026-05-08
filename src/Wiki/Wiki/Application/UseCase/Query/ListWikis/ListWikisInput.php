<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query\ListWikis;

use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

readonly class ListWikisInput implements ListWikisInputPort
{
    public function __construct(
        private Language $language,
        private ?int $perPage = null,
        private ?ResourceType $resourceType = null,
        private ?string $keyword = null,
        private ?string $sort = null,
        private ?string $order = null,
    ) {
    }

    public function language(): Language
    {
        return $this->language;
    }

    public function perPage(): int
    {
        return $this->perPage ?? 10;
    }

    public function resourceType(): ?ResourceType
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
