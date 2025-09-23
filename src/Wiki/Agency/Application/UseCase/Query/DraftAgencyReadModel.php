<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Application\UseCase\Query;

use DateTimeImmutable;

readonly class DraftAgencyReadModel
{
    /**
     * @param string $agencyId
     * @param string|null $publishedAgencyId
     * @param string $name
     * @param string $CEO
     * @param DateTimeImmutable $foundedIn
     * @param string $description
     * @param string $status
     */
    public function __construct(
        private string $agencyId,
        private ?string $publishedAgencyId,
        private string $name,
        private string  $CEO,
        private DateTimeImmutable $foundedIn,
        private string $description,
        private string $status,
    ) {
    }

    public function agencyId(): string
    {
        return $this->agencyId;
    }

    public function publishedAgencyId(): ?string
    {
        return $this->publishedAgencyId;
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

    public function status(): string
    {
        return $this->status;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'agency_id' => $this->agencyId,
            'published_agency_id' => $this->publishedAgencyId,
            'name' => $this->name,
            'CEO' => $this->CEO,
            'founded_in' => $this->foundedIn->format('Y'),
            'description' => $this->description,
            'status' => $this->status,
        ];
    }
}
