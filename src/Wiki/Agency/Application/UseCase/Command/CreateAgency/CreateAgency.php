<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Application\UseCase\Command\CreateAgency;

use Source\Wiki\Agency\Domain\Entity\DraftAgency;
use Source\Wiki\Agency\Domain\Factory\DraftAgencyFactoryInterface;
use Source\Wiki\Agency\Domain\Repository\AgencyRepositoryInterface;
use Source\Wiki\Agency\Domain\Repository\DraftAgencyRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\Service\NormalizationServiceInterface;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

readonly class CreateAgency implements CreateAgencyInterface
{
    public function __construct(
        private DraftAgencyFactoryInterface    $agencyFactory,
        private AgencyRepositoryInterface      $agencyRepository,
        private DraftAgencyRepositoryInterface $draftAgencyRepository,
        private NormalizationServiceInterface  $normalizationService,
        private PrincipalRepositoryInterface   $principalRepository,
    ) {
    }

    /**
     * @param CreateAgencyInputPort $input
     * @return DraftAgency
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function process(CreateAgencyInputPort $input): DraftAgency
    {
        $principal = $this->principalRepository->findById($input->principalIdentifier());

        if ($principal === null) {
            throw new PrincipalNotFoundException();
        }

        $resourceIdentifier = new ResourceIdentifier(
            type: ResourceType::AGENCY,
            agencyId: null,
            groupIds: [],
        );

        if (! $principal->role()->can(Action::CREATE, $resourceIdentifier, $principal)) {
            throw new UnauthorizedException();
        }

        $agency = $this->agencyFactory->create(
            $input->principalIdentifier(),
            $input->language(),
            $input->name(),
        );
        if ($input->publishedAgencyIdentifier()) {
            $publishedAgency = $this->agencyRepository->findById($input->publishedAgencyIdentifier());
            if ($publishedAgency) {
                $agency->setPublishedAgencyIdentifier($publishedAgency->agencyIdentifier());
            }
        }
        $agency->setCEO($input->CEO());
        $normalizedCEO = $this->normalizationService->normalize((string)$agency->CEO(), $agency->language());
        $agency->setNormalizedCEO($normalizedCEO);
        if ($input->foundedIn()) {
            $agency->setFoundedIn($input->foundedIn());
        }
        $agency->setDescription($input->description());

        $this->draftAgencyRepository->save($agency);

        return $agency;
    }
}
