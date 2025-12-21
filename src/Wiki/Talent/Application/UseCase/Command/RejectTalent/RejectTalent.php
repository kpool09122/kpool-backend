<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Application\UseCase\Command\RejectTalent;

use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Talent\Application\Exception\TalentNotFoundException;
use Source\Wiki\Talent\Domain\Entity\DraftTalent;
use Source\Wiki\Talent\Domain\Factory\TalentHistoryFactoryInterface;
use Source\Wiki\Talent\Domain\Repository\TalentHistoryRepositoryInterface;
use Source\Wiki\Talent\Domain\Repository\TalentRepositoryInterface;

readonly class RejectTalent implements RejectTalentInterface
{
    public function __construct(
        private TalentRepositoryInterface        $talentRepository,
        private TalentHistoryRepositoryInterface $talentHistoryRepository,
        private TalentHistoryFactoryInterface    $talentHistoryFactory,
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
            static fn ($groupIdentifier) => (string) $groupIdentifier,
            $talent->groupIdentifiers()
        );
        $resourceIdentifier = new ResourceIdentifier(
            type: ResourceType::TALENT,
            agencyId: (string) $talent->agencyIdentifier(),
            groupIds: $groupIds,
            talentIds: [(string) $talent->talentIdentifier()],
        );

        if (! $principal->role()->can(Action::REJECT, $resourceIdentifier, $principal)) {
            throw new UnauthorizedException();
        }

        $previousStatus = $talent->status();

        if ($previousStatus !== ApprovalStatus::UnderReview) {
            throw new InvalidStatusException();
        }

        $talent->setStatus(ApprovalStatus::Rejected);

        $this->talentRepository->saveDraft($talent);

        $history = $this->talentHistoryFactory->create(
            new EditorIdentifier((string) $input->principal()->principalIdentifier()),
            $talent->editorIdentifier(),
            $talent->publishedTalentIdentifier(),
            $talent->talentIdentifier(),
            $previousStatus,
            $talent->status(),
            $talent->name(),
        );
        $this->talentHistoryRepository->save($history);

        return $talent;
    }
}
