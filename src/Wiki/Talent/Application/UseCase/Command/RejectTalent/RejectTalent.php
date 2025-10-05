<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Application\UseCase\Command\RejectTalent;

use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Talent\Application\Exception\TalentNotFoundException;
use Source\Wiki\Talent\Domain\Entity\DraftTalent;
use Source\Wiki\Talent\Domain\Repository\TalentRepositoryInterface;

class RejectTalent implements RejectTalentInterface
{
    public function __construct(
        private TalentRepositoryInterface $talentRepository,
    ) {
    }

    /**
     * @param RejectTalentInputPort $input
     * @return DraftTalent
     * @throws TalentNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     */
    public function process(RejectTalentInputPort $input): DraftTalent
    {
        $talent = $this->talentRepository->findDraftById($input->talentIdentifier());

        if ($talent === null) {
            throw new TalentNotFoundException();
        }

        $principal = $input->principal();
        $groupIds = array_map(
            fn ($groupIdentifier) => (string) $groupIdentifier,
            $talent->groupIdentifiers()
        );
        $resourceIdentifier = new ResourceIdentifier(
            type: ResourceType::TALENT,
            agencyId: null,
            groupIds: $groupIds,
            talentId: (string) $talent->talentIdentifier(),
        );

        if (! $principal->role()->can(Action::REJECT, $resourceIdentifier, $principal)) {
            throw new UnauthorizedException();
        }

        if ($talent->status() !== ApprovalStatus::UnderReview) {
            throw new InvalidStatusException();
        }

        $talent->setStatus(ApprovalStatus::Rejected);

        $this->talentRepository->saveDraft($talent);

        return $talent;
    }
}
