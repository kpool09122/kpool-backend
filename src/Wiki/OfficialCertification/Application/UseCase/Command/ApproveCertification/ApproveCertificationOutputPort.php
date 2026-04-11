<?php

declare(strict_types=1);

namespace Source\Wiki\OfficialCertification\Application\UseCase\Command\ApproveCertification;

use Source\Wiki\OfficialCertification\Domain\Entity\OfficialCertification;

interface ApproveCertificationOutputPort
{
    public function setOfficialCertification(OfficialCertification $officialCertification): void;

    /**
     * @return array{certificationIdentifier: ?string, resourceType: ?string, wikiIdentifier: ?string, status: ?string}
     */
    public function toArray(): array;
}
