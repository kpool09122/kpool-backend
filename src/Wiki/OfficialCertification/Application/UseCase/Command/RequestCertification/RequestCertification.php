<?php

declare(strict_types=1);

namespace Source\Wiki\OfficialCertification\Application\UseCase\Command\RequestCertification;

use Source\Wiki\OfficialCertification\Application\Exception\OfficialCertificationAlreadyRequestedException;
use Source\Wiki\OfficialCertification\Domain\Entity\OfficialCertification;
use Source\Wiki\OfficialCertification\Domain\Factory\OfficialCertificationFactoryInterface;
use Source\Wiki\OfficialCertification\Domain\Repository\OfficialCertificationRepositoryInterface;

readonly class RequestCertification implements RequestCertificationInterface
{
    public function __construct(
        private OfficialCertificationRepositoryInterface $repository,
        private OfficialCertificationFactoryInterface $factory,
    ) {
    }

    public function process(RequestCertificationInputPort $input): OfficialCertification
    {
        $existing = $this->repository->findByResource(
            $input->resourceType(),
            $input->resourceIdentifier(),
        );

        if ($existing !== null) {
            throw new OfficialCertificationAlreadyRequestedException();
        }

        $certification = $this->factory->create(
            $input->resourceType(),
            $input->resourceIdentifier(),
            $input->ownerAccountIdentifier(),
        );

        $this->repository->save($certification);

        return $certification;
    }
}
