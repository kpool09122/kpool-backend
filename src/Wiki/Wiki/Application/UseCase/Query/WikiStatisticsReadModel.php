<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query;

readonly class WikiStatisticsReadModel
{
    /**
     * @param array<string, int> $languages
     * @param array<string, array{resourceTypes: array<string, int>, groupTypes: array<string, int>}> $byLanguage
     */
    public function __construct(
        private int $total,
        private array $languages,
        private array $byLanguage,
    ) {
    }

    /** @return array{total: int, languages: array<string, int>, byLanguage: array<string, array{resourceTypes: array<string, int>, groupTypes: array<string, int>}>} */
    public function toArray(): array
    {
        return [
            'total' => $this->total,
            'languages' => $this->languages,
            'byLanguage' => $this->byLanguage,
        ];
    }
}
