<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Application\UseCase\Command\MergeAgency;

use Source\Wiki\Agency\Application\Exception\AgencyNotFoundException;
use Source\Wiki\Agency\Domain\Entity\DraftAgency;
use Source\Wiki\Agency\Domain\Repository\DraftAgencyRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\Service\NormalizationServiceInterface;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\Resource;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

readonly class MergeAgency implements MergeAgencyInterface
{
    public function __construct(
        private DraftAgencyRepositoryInterface $draftAgencyRepository,
        private NormalizationServiceInterface  $normalizationService,
        private PrincipalRepositoryInterface   $principalRepository,
        private PolicyEvaluatorInterface       $policyEvaluator,
    ) {
    }

    /**
     * @param MergeAgencyInputPort $input
     * @return DraftAgency
     * @throws AgencyNotFoundException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function process(MergeAgencyInputPort $input): DraftAgency
    {
        $agency = $this->draftAgencyRepository->findById($input->agencyIdentifier());

        if ($agency === null) {
            throw new AgencyNotFoundException();
        }

        $principal = $this->principalRepository->findById($input->principalIdentifier());
        if ($principal === null) {
            throw new PrincipalNotFoundException();
        }
        $resource = new Resource(
            type: ResourceType::AGENCY,
            agencyId: (string) $agency->agencyIdentifier(),
            groupIds: [],
        );

        if (! $this->policyEvaluator->evaluate($principal, Action::MERGE, $resource)) {
            throw new UnauthorizedException();
        }

        $agency->setName($input->name());
        $normalizedName = $this->normalizationService->normalize((string)$agency->name(), $agency->language());
        $agency->setNormalizedName($normalizedName);
        $agency->setCEO($input->CEO());
        $normalizedCEO = $this->normalizationService->normalize((string)$agency->CEO(), $agency->language());
        $agency->setNormalizedCEO($normalizedCEO);
        if ($input->foundedIn()) {
            $agency->setFoundedIn($input->foundedIn());
        }
        $agency->setDescription($input->description());
        $agency->setMergerIdentifier($input->principalIdentifier());
        $agency->setMergedAt($input->mergedAt());

        $this->draftAgencyRepository->save($agency);

        return $agency;
    }
}
