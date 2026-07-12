<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query;

readonly class GroupWikiBasicReadModel implements WikiBasicReadModel
{
    use ArrayAccessibleReadModel;

    /**
     * @param list<string> $officialColors
     */
    public function __construct(
        private string $name,
        private string $normalizedName,
        private ?string $agencyIdentifier,
        private ?WikiAgencySummaryReadModel $agency,
        private ?string $groupType,
        private ?string $status,
        private ?string $generation,
        private ?string $debutDate,
        private ?string $disbandDate,
        private string $fandomName,
        private array $officialColors,
        private string $emoji,
        private string $representativeSymbol,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'normalizedName' => $this->normalizedName,
            'agencyIdentifier' => $this->agencyIdentifier,
            'agency' => $this->agency?->toArray(),
            'groupType' => $this->groupType,
            'status' => $this->status,
            'generation' => $this->generation,
            'debutDate' => $this->debutDate,
            'disbandDate' => $this->disbandDate,
            'fandomName' => $this->fandomName,
            'officialColors' => $this->officialColors,
            'emoji' => $this->emoji,
            'representativeSymbol' => $this->representativeSymbol,
        ];
    }
}
