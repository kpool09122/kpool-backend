<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Application\UseCase\Command\AutomaticCreateDraftTalent;

use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\ValueObject\Role;
use Source\Wiki\Talent\Domain\Entity\DraftTalent;
use Source\Wiki\Talent\Domain\Repository\TalentRepositoryInterface;
use Source\Wiki\Talent\Domain\Service\AutomaticDraftTalentCreationServiceInterface;

readonly class AutomaticCreateDraftTalent implements AutomaticCreateDraftTalentInterface
{
    public function __construct(
        private AutomaticDraftTalentCreationServiceInterface $automaticDraftTalentCreationService,
        private TalentRepositoryInterface $talentRepository,
    ) {
    }

    /**
     * @param AutomaticCreateDraftTalentInputPort $input
     * @return DraftTalent
     * @throws UnauthorizedException
     */
    public function process(AutomaticCreateDraftTalentInputPort $input): DraftTalent
    {
        $principal = $input->principal();

        $role = $principal->role();
        if ($role !== Role::ADMINISTRATOR && $role !== Role::SENIOR_COLLABORATOR) {
            throw new UnauthorizedException();
        }

        $draftTalent = $this->automaticDraftTalentCreationService->create($input->payload(), $principal);
        $this->talentRepository->saveDraft($draftTalent);

        return $draftTalent;
    }
}
