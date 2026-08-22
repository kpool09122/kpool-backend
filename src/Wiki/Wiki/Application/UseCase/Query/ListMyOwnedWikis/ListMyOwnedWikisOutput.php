<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query\ListMyOwnedWikis;

use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Wiki\Wiki\Application\UseCase\Query\WikiListItemReadModel;

class ListMyOwnedWikisOutput implements ListMyOwnedWikisOutputPort
{
    private ?AccountCategory $accountCategory = null;

    /** @var list<WikiListItemReadModel> */
    private array $primaryOwnedWikis = [];

    /** @var list<WikiListItemReadModel> */
    private array $otherOwnedWikis = [];

    private ?int $currentPage = null;

    private ?int $lastPage = null;

    private ?int $total = null;

    private ?int $perPage = null;

    /**
     * @param list<WikiListItemReadModel> $primaryOwnedWikis
     * @param list<WikiListItemReadModel> $otherOwnedWikis
     */
    public function output(
        AccountCategory $accountCategory,
        array $primaryOwnedWikis,
        array $otherOwnedWikis,
        int $currentPage,
        int $lastPage,
        int $total,
        int $perPage,
    ): void {
        $this->accountCategory = $accountCategory;
        $this->primaryOwnedWikis = $primaryOwnedWikis;
        $this->otherOwnedWikis = $otherOwnedWikis;
        $this->currentPage = $currentPage;
        $this->lastPage = $lastPage;
        $this->total = $total;
        $this->perPage = $perPage;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'accountCategory' => $this->accountCategory?->value,
            'primaryOwnedWikis' => array_map(static fn (WikiListItemReadModel $wiki): array => $wiki->toArray(), $this->primaryOwnedWikis),
            'otherOwnedWikis' => array_map(static fn (WikiListItemReadModel $wiki): array => $wiki->toArray(), $this->otherOwnedWikis),
            'current_page' => $this->currentPage,
            'last_page' => $this->lastPage,
            'total' => $this->total,
            'per_page' => $this->perPage,
        ];
    }
}
