<?php

declare(strict_types=1);

namespace Source\Wiki\OfficialCertification\Application\UseCase\Query\ListMyOfficialCertifications;

use Source\Wiki\OfficialCertification\Application\UseCase\Query\OfficialCertificationListItemReadModel;

class ListMyOfficialCertificationsOutput implements ListMyOfficialCertificationsOutputPort
{
    /** @var list<OfficialCertificationListItemReadModel> */
    private array $certifications = [];

    private ?int $currentPage = null;

    private ?int $lastPage = null;

    private ?int $total = null;

    private ?int $perPage = null;

    /**
     * @param list<OfficialCertificationListItemReadModel> $certifications
     */
    public function output(array $certifications, int $currentPage, int $lastPage, int $total, int $perPage): void
    {
        $this->certifications = $certifications;
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
            'officialCertifications' => array_map(
                static fn (OfficialCertificationListItemReadModel $certification): array => $certification->toArray(),
                $this->certifications,
            ),
            'current_page' => $this->currentPage,
            'last_page' => $this->lastPage,
            'total' => $this->total,
            'per_page' => $this->perPage,
        ];
    }
}
