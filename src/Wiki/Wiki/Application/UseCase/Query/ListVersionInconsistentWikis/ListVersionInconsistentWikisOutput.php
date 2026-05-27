<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query\ListVersionInconsistentWikis;

use Source\Wiki\Wiki\Application\UseCase\Query\WikiListItemReadModel;

class ListVersionInconsistentWikisOutput implements ListVersionInconsistentWikisOutputPort
{
    /** @var list<WikiListItemReadModel> */
    private array $wikis = [];

    private ?int $currentPage = null;

    private ?int $lastPage = null;

    private ?int $total = null;

    private ?int $perPage = null;

    /**
     * @param list<WikiListItemReadModel> $wikis
     */
    public function output(array $wikis, int $currentPage, int $lastPage, int $total, int $perPage): void
    {
        $this->wikis = $wikis;
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
            'wikis' => array_map(static fn (WikiListItemReadModel $wiki): array => $wiki->toArray(), $this->wikis),
            'current_page' => $this->currentPage,
            'last_page' => $this->lastPage,
            'total' => $this->total,
            'per_page' => $this->perPage,
        ];
    }
}
