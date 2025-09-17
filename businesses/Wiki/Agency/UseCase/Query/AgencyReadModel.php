<?php

declare(strict_types=1);

namespace Businesses\Wiki\Agency\UseCase\Query;

use DateTimeImmutable;

readonly class AgencyReadModel
{
    /**
     * @param string $agencyId
     * @param string $name
     * @param string $CEO
     * @param DateTimeImmutable $foundedIn
     * @param string $description
     */
    public function __construct(
        private string $agencyId,
        private string $name,
        private string $CEO,
        private DateTimeImmutable $foundedIn,
        private string $description,
    ) {
    }

    public function agencyId(): string
    {
        return $this->agencyId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function CEO(): string
    {
        return $this->CEO;
    }

    public function foundedIn(): DateTimeImmutable
    {
        return $this->foundedIn;
    }

    public function description(): string
    {
        return $this->description;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'agency_id' => $this->agencyId,
            'name' => $this->name,
            'CEO' => $this->CEO,
            'founded_in' => $this->foundedIn->format('Y'),
            'description' => $this->description,
        ];
    }
}
