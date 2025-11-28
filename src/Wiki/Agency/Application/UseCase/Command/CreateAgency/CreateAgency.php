<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Application\UseCase\Command\CreateAgency;

use Source\Wiki\Agency\Domain\Entity\DraftAgency;
use Source\Wiki\Agency\Domain\Factory\DraftAgencyFactoryInterface;
use Source\Wiki\Agency\Domain\Repository\AgencyRepositoryInterface;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

class CreateAgency implements CreateAgencyInterface
{
    public function __construct(
        private DraftAgencyFactoryInterface $agencyFactory,
        private AgencyRepositoryInterface   $agencyRepository,
    ) {
    }

    /**
     * @param CreateAgencyInputPort $input
     * @return DraftAgency
     * @throws UnauthorizedException
     */
    public function process(CreateAgencyInputPort $input): DraftAgency
    {
        $principal = $input->principal();
        $resourceIdentifier = new ResourceIdentifier(
            type: ResourceType::AGENCY,
            agencyId: null,
            groupIds: [],
        );

        if (! $principal->role()->can(Action::CREATE, $resourceIdentifier, $principal)) {
            throw new UnauthorizedException();
        }

        $agency = $this->agencyFactory->create(
            $input->editorIdentifier(),
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
        if ($input->foundedIn()) {
            $agency->setFoundedIn($input->foundedIn());
        }
        $agency->setDescription($input->description());

        $this->agencyRepository->saveDraft($agency);

        return $agency;
    }
}
