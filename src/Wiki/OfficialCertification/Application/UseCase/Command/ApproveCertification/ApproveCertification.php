<?php

declare(strict_types=1);

namespace Source\Wiki\OfficialCertification\Application\UseCase\Command\ApproveCertification;

use Source\Wiki\OfficialCertification\Application\Exception\OfficialCertificationInvalidStatusException;
use Source\Wiki\OfficialCertification\Application\Exception\OfficialCertificationNotFoundException;
use Source\Wiki\OfficialCertification\Application\Service\OfficialResourceUpdaterInterface;
use Source\Wiki\OfficialCertification\Domain\Entity\OfficialCertification;
use Source\Wiki\OfficialCertification\Domain\Repository\OfficialCertificationRepositoryInterface;

readonly class ApproveCertification implements ApproveCertificationInterface
{
    public function __construct(
        private OfficialCertificationRepositoryInterface $repository,
        private OfficialResourceUpdaterInterface $resourceUpdater,
    ) {
    }

    public function process(ApproveCertificationInputPort $input): OfficialCertification
    {
        $certification = $this->repository->findById($input->certificationIdentifier());

        if ($certification === null) {
            throw new OfficialCertificationNotFoundException();
        }

        if (! $certification->isPending()) {
            throw new OfficialCertificationInvalidStatusException();
        }

        $certification->approve();

        $this->repository->save($certification);

        $this->resourceUpdater->markOfficial(
            $certification->resourceType(),
            $certification->resourceIdentifier(),
            $certification->ownerAccountIdentifier(),
        );

        return $certification;
    }
}
