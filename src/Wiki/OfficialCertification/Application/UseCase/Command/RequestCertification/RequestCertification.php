<?php

declare(strict_types=1);

namespace Source\Wiki\OfficialCertification\Application\UseCase\Command\RequestCertification;

use Source\Wiki\OfficialCertification\Application\Exception\OfficialCertificationAlreadyRequestedException;
use Source\Wiki\OfficialCertification\Domain\Factory\OfficialCertificationFactoryInterface;
use Source\Wiki\OfficialCertification\Domain\Repository\OfficialCertificationRepositoryInterface;

readonly class RequestCertification implements RequestCertificationInterface
{
    public function __construct(
        private OfficialCertificationRepositoryInterface $repository,
        private OfficialCertificationFactoryInterface $factory,
    ) {
    }

    /**
     * @param RequestCertificationInputPort $input
     * @param RequestCertificationOutputPort $output
     * @return void
     * @throws OfficialCertificationAlreadyRequestedException
     */
    public function process(RequestCertificationInputPort $input, RequestCertificationOutputPort $output): void
    {
        $existing = $this->repository->findByResource(
            $input->resourceType(),
            $input->wikiIdentifier(),
        );

        if ($existing !== null) {
            throw new OfficialCertificationAlreadyRequestedException();
        }

        $certification = $this->factory->create(
            $input->resourceType(),
            $input->wikiIdentifier(),
            $input->ownerAccountIdentifier(),
        );

        $this->repository->save($certification);

        $output->setOfficialCertification($certification);
    }
}
