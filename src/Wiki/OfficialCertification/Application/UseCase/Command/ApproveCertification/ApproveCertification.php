<?php

declare(strict_types=1);

namespace Source\Wiki\OfficialCertification\Application\UseCase\Command\ApproveCertification;

use Source\Wiki\OfficialCertification\Application\Exception\OfficialCertificationInvalidStatusException;
use Source\Wiki\OfficialCertification\Application\Exception\OfficialCertificationNotFoundException;
use Source\Wiki\OfficialCertification\Application\Service\OfficialResourceUpdaterInterface;
use Source\Wiki\OfficialCertification\Domain\Entity\OfficialCertification;
use Source\Wiki\OfficialCertification\Domain\Repository\OfficialCertificationRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\Resource;

readonly class ApproveCertification implements ApproveCertificationInterface
{
    public function __construct(
        private OfficialCertificationRepositoryInterface $repository,
        private OfficialResourceUpdaterInterface $resourceUpdater,
        private PrincipalRepositoryInterface $principalRepository,
        private PolicyEvaluatorInterface $policyEvaluator,
    ) {
    }

    /**
     * @throws OfficialCertificationNotFoundException
     * @throws OfficialCertificationInvalidStatusException
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function process(ApproveCertificationInputPort $input, ApproveCertificationOutputPort $output): void
    {
        $certification = $this->repository->findById($input->certificationIdentifier());

        if ($certification === null) {
            throw new OfficialCertificationNotFoundException();
        }

        if (! $certification->isPending()) {
            throw new OfficialCertificationInvalidStatusException();
        }

        $this->assertAllowed($input, $certification);

        $certification->approve();

        $this->repository->save($certification);

        $this->resourceUpdater->markOfficial(
            $certification->resourceType(),
            $certification->wikiIdentifier(),
            $certification->ownerAccountIdentifier(),
        );

        $output->setOfficialCertification($certification);
    }

    private function assertAllowed(ApproveCertificationInputPort $input, OfficialCertification $certification): void
    {
        $principal = $this->principalRepository->findById($input->operatorPrincipalIdentifier());
        if ($principal === null) {
            throw new PrincipalNotFoundException();
        }

        if (! $this->policyEvaluator->evaluate(
            $principal,
            Action::OFFICIAL_CERTIFICATION_APPROVE,
            new Resource(type: $certification->resourceType()),
        )) {
            throw new DisallowedException();
        }
    }
}
