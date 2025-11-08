<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Application\UseCase\Command\SubmitTalent;

use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Talent\Application\Exception\TalentNotFoundException;
use Source\Wiki\Talent\Domain\Entity\DraftTalent;
use Source\Wiki\Talent\Domain\Repository\TalentRepositoryInterface;

readonly class SubmitTalent implements SubmitTalentInterface
{
    public function __construct(
        private TalentRepositoryInterface $talentRepository,
    ) {
    }

    /**
     * @param SubmitTalentInputPort $input
     * @return DraftTalent
     * @throws TalentNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     */
    public function process(SubmitTalentInputPort $input): DraftTalent
    {
        $talent = $this->talentRepository->findDraftById($input->talentIdentifier());

        if ($talent === null) {
            throw new TalentNotFoundException();
        }

        $principal = $input->principal();
        $groupIds = array_map(
            static fn ($groupIdentifier) => (string) $groupIdentifier,
            $talent->groupIdentifiers()
        );
        $resourceIdentifier = new ResourceIdentifier(
            type: ResourceType::TALENT,
            agencyId: (string) $talent->agencyIdentifier(),
            groupIds: $groupIds,
            talentIds: [(string) $talent->talentIdentifier()],
        );

        if (! $principal->role()->can(Action::SUBMIT, $resourceIdentifier, $principal)) {
            throw new UnauthorizedException();
        }

        if ($talent->status() !== ApprovalStatus::Pending
        && $talent->status() !== ApprovalStatus::Rejected) {
            throw new InvalidStatusException();
        }

        $talent->setStatus(ApprovalStatus::UnderReview);

        $this->talentRepository->saveDraft($talent);

        return $talent;
    }
}
