<?php

declare(strict_types=1);

namespace Source\Wiki\OfficialCertification\Application\UseCase\Command\RejectCertification;

use Source\Wiki\OfficialCertification\Application\Exception\OfficialCertificationInvalidStatusException;
use Source\Wiki\OfficialCertification\Application\Exception\OfficialCertificationNotFoundException;
use Source\Wiki\OfficialCertification\Domain\Repository\OfficialCertificationRepositoryInterface;

readonly class RejectCertification implements RejectCertificationInterface
{
    public function __construct(
        private OfficialCertificationRepositoryInterface $repository,
    ) {
    }

    /**
     * @param RejectCertificationInputPort $input
     * @param RejectCertificationOutputPort $output
     * @return void
     * @throws OfficialCertificationNotFoundException
     * @throws OfficialCertificationInvalidStatusException
     */
    public function process(RejectCertificationInputPort $input, RejectCertificationOutputPort $output): void
    {
        $certification = $this->repository->findById($input->certificationIdentifier());

        if ($certification === null) {
            throw new OfficialCertificationNotFoundException();
        }

        if (! $certification->isPending()) {
            throw new OfficialCertificationInvalidStatusException();
        }

        $certification->reject();

        $this->repository->save($certification);

        $output->setOfficialCertification($certification);
    }
}
