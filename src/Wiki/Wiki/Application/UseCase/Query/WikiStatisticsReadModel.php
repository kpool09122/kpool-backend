<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query;

readonly class WikiStatisticsReadModel
{
    /**
     * @param array<string, int> $languages
     * @param array<string, int> $resourceTypes
     * @param array<string, int> $groupTypes
     * @param array<string, array{resourceTypes: array<string, int>, groupTypes: array<string, int>}> $byLanguage
     */
    public function __construct(
        private int $total,
        private array $languages,
        private array $resourceTypes,
        private array $groupTypes,
        private array $byLanguage,
    ) {
    }

    /** @return array{total: int, languages: array<string, int>, resourceTypes: array<string, int>, groupTypes: array<string, int>, byLanguage: array<string, array{resourceTypes: array<string, int>, groupTypes: array<string, int>}>} */
    public function toArray(): array
    {
        return [
            'total' => $this->total,
            'languages' => $this->languages,
            'resourceTypes' => $this->resourceTypes,
            'groupTypes' => $this->groupTypes,
            'byLanguage' => $this->byLanguage,
        ];
    }
}
