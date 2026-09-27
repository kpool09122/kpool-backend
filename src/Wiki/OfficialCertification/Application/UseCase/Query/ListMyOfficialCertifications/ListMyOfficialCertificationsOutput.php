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
     * @return array{officialCertifications: list<array{certificationIdentifier: string, resourceType: string, translationSetIdentifier: string, ownerAccount: array{accountIdentifier: string, email: string, type: string, name: string, status: string, category: string}|null, wikis: list<array{wikiIdentifier: string, translationSetIdentifier: string, slug: string, language: string, resourceType: string, version: int, isOfficial: bool, themeColor: string|null, fontStyle: string|null, title: string|null, metaDescription: string|null, keywords: list<string>|null, imageIdentifier: string|null, imageUrl: string|null, imageAltText: string|null, isHidden: bool|null, name: string, normalizedName: string, publishedAt: string|null, updatedAt: string|null}>, status: string, requestedAt: string, approvedAt: string|null, rejectedAt: string|null}>, current_page: int|null, last_page: int|null, total: int|null, per_page: int|null}
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
