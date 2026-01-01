<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Application\UseCase\Command\AutomaticCreateDraftAgency;

use Source\Wiki\Agency\Domain\Entity\DraftAgency;
use Source\Wiki\Agency\Domain\Repository\DraftAgencyRepositoryInterface;
use Source\Wiki\Agency\Domain\Service\AutomaticDraftAgencyCreationServiceInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\ValueObject\Role;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;

readonly class AutomaticCreateDraftAgency implements AutomaticCreateDraftAgencyInterface
{
    public function __construct(
        private AutomaticDraftAgencyCreationServiceInterface $automaticDraftAgencyCreationService,
        private DraftAgencyRepositoryInterface               $agencyRepository,
        private PrincipalRepositoryInterface                 $principalRepository,
    ) {
    }

    /**
     * @param AutomaticCreateDraftAgencyInputPort $input
     * @return DraftAgency
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function process(AutomaticCreateDraftAgencyInputPort $input): DraftAgency
    {
        $principal = $this->principalRepository->findById($input->principalIdentifier());

        if ($principal === null) {
            throw new PrincipalNotFoundException();
        }

        $role = $principal->role();
        if ($role !== Role::ADMINISTRATOR && $role !== Role::SENIOR_COLLABORATOR) {
            throw new UnauthorizedException();
        }

        $draftAgency = $this->automaticDraftAgencyCreationService->create($input->payload(), $principal);
        $this->agencyRepository->save($draftAgency);

        return $draftAgency;
    }
}
