<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query;

readonly class AgencyWikiBasicReadModel implements WikiBasicReadModel
{
    use ArrayAccessibleReadModel;

    /**
     * @param list<string> $socialLinks
     */
    public function __construct(
        private string $name,
        private string $normalizedName,
        private string $ceo,
        private string $normalizedCeo,
        private ?string $foundedIn,
        private ?string $parentAgencyIdentifier,
        private ?string $status,
        private ?string $officialWebsite,
        private array $socialLinks,
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
            'ceo' => $this->ceo,
            'normalizedCeo' => $this->normalizedCeo,
            'foundedIn' => $this->foundedIn,
            'parentAgencyIdentifier' => $this->parentAgencyIdentifier,
            'status' => $this->status,
            'officialWebsite' => $this->officialWebsite,
            'socialLinks' => $this->socialLinks,
        ];
    }
}
