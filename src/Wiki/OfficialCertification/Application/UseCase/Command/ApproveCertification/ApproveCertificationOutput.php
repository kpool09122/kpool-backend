<?php

declare(strict_types=1);

namespace Source\Wiki\OfficialCertification\Application\UseCase\Command\ApproveCertification;

use Source\Wiki\OfficialCertification\Domain\Entity\OfficialCertification;

class ApproveCertificationOutput implements ApproveCertificationOutputPort
{
    private ?OfficialCertification $officialCertification = null;

    public function setOfficialCertification(OfficialCertification $officialCertification): void
    {
        $this->officialCertification = $officialCertification;
    }

    /**
     * @return array{certificationIdentifier: ?string, resourceType: ?string, wikiIdentifier: ?string, status: ?string}
     */
    public function toArray(): array
    {
        if ($this->officialCertification === null) {
            return [
                'certificationIdentifier' => null,
                'resourceType' => null,
                'wikiIdentifier' => null,
                'status' => null,
            ];
        }

        return [
            'certificationIdentifier' => (string) $this->officialCertification->certificationIdentifier(),
            'resourceType' => $this->officialCertification->resourceType()->value,
            'wikiIdentifier' => (string) $this->officialCertification->wikiIdentifier(),
            'status' => $this->officialCertification->status()->value,
        ];
    }
}
