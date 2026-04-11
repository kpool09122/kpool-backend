<?php

declare(strict_types=1);

namespace Source\Wiki\OfficialCertification\Application\UseCase\Command\RequestCertification;

use Source\Wiki\OfficialCertification\Domain\Entity\OfficialCertification;

interface RequestCertificationOutputPort
{
    public function setOfficialCertification(OfficialCertification $officialCertification): void;

    /**
     * @return array{certificationIdentifier: ?string, resourceType: ?string, wikiIdentifier: ?string, status: ?string}
     */
    public function toArray(): array;
}
