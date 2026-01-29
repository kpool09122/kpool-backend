<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Application\UseCase\Command\AutoCreateAgency;

use Source\Wiki\Shared\Application\DTO\SourceReference;

final readonly class GeneratedAgencyData
{
    /**
     * @param string|null $alphabetName
     * @param string|null $ceoName
     * @param int|null $foundedYear
     * @param string|null $description
     * @param SourceReference[] $sources
     */
    public function __construct(
        private ?string $alphabetName,
        private ?string $ceoName,
        private ?int $foundedYear,
        private ?string $description,
        private array $sources,
    ) {
    }

    public function alphabetName(): ?string
    {
        return $this->alphabetName;
    }

    public function ceoName(): ?string
    {
        return $this->ceoName;
    }

    public function foundedYear(): ?int
    {
        return $this->foundedYear;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    /**
     * @return SourceReference[]
     */
    public function sources(): array
    {
        return $this->sources;
    }
}
