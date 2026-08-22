<?php

declare(strict_types=1);

namespace Source\Wiki\OfficialCertification\Application\UseCase\Command\RejectCertification;

use Source\Wiki\OfficialCertification\Application\Exception\OfficialCertificationInvalidStatusException;
use Source\Wiki\OfficialCertification\Application\Exception\OfficialCertificationNotFoundException;
use Source\Wiki\OfficialCertification\Domain\Entity\OfficialCertification;
use Source\Wiki\OfficialCertification\Domain\Repository\OfficialCertificationRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\Resource;

readonly class RejectCertification implements RejectCertificationInterface
{
    public function __construct(
        private OfficialCertificationRepositoryInterface $repository,
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
    public function process(RejectCertificationInputPort $input, RejectCertificationOutputPort $output): void
    {
        $certification = $this->repository->findById($input->certificationIdentifier());

        if ($certification === null) {
            throw new OfficialCertificationNotFoundException();
        }

        if (! $certification->isPending()) {
            throw new OfficialCertificationInvalidStatusException();
        }

        $this->assertAllowed($input, $certification);

        $certification->reject();

        $this->repository->save($certification);

        $output->setOfficialCertification($certification);
    }

    private function assertAllowed(RejectCertificationInputPort $input, OfficialCertification $certification): void
    {
        $principal = $this->principalRepository->findById($input->operatorPrincipalIdentifier());
        if ($principal === null) {
            throw new PrincipalNotFoundException();
        }

        if (! $this->policyEvaluator->evaluate(
            $principal,
            Action::OFFICIAL_CERTIFICATION_REJECT,
            new Resource(type: $certification->resourceType()),
        )) {
            throw new DisallowedException();
        }
    }
}
