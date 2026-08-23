<?php

declare(strict_types=1);

namespace Source\Wiki\OfficialCertification\Application\UseCase\Query\ListOfficialCertifications;

use Source\Wiki\OfficialCertification\Application\UseCase\Query\OfficialCertificationListItemReadModel;

interface ListOfficialCertificationsOutputPort
{
    /**
     * @param list<OfficialCertificationListItemReadModel> $certifications
     */
    public function output(array $certifications, int $currentPage, int $lastPage, int $total, int $perPage): void;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
