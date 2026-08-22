<?php

declare(strict_types=1);

namespace Source\Wiki\OfficialCertification\Application\UseCase\Command\RequestCertification;

use Source\Wiki\OfficialCertification\Domain\Entity\OfficialCertification;

class RequestCertificationOutput implements RequestCertificationOutputPort
{
    private ?OfficialCertification $officialCertification = null;

    public function setOfficialCertification(OfficialCertification $officialCertification): void
    {
        $this->officialCertification = $officialCertification;
    }

    /**
     * @return array{certificationIdentifier: ?string, resourceType: ?string, translationSetIdentifier: ?string, status: ?string}
     */
    public function toArray(): array
    {
        if ($this->officialCertification === null) {
            return [
                'certificationIdentifier' => null,
                'resourceType' => null,
                'translationSetIdentifier' => null,
                'status' => null,
            ];
        }

        return [
            'certificationIdentifier' => (string) $this->officialCertification->certificationIdentifier(),
            'resourceType' => $this->officialCertification->resourceType()->value,
            'translationSetIdentifier' => (string) $this->officialCertification->translationSetIdentifier(),
            'status' => $this->officialCertification->status()->value,
        ];
    }
}
