<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Application\UseCase\Command\RejectTalent;

use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\HistoryActionType;
use Source\Wiki\Shared\Domain\ValueObject\Resource;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Talent\Application\Exception\TalentNotFoundException;
use Source\Wiki\Talent\Domain\Entity\DraftTalent;
use Source\Wiki\Talent\Domain\Factory\TalentHistoryFactoryInterface;
use Source\Wiki\Talent\Domain\Repository\DraftTalentRepositoryInterface;
use Source\Wiki\Talent\Domain\Repository\TalentHistoryRepositoryInterface;

readonly class RejectTalent implements RejectTalentInterface
{
    public function __construct(
        private DraftTalentRepositoryInterface   $dratTalentRepository,
        private TalentHistoryRepositoryInterface $talentHistoryRepository,
        private TalentHistoryFactoryInterface    $talentHistoryFactory,
        private PrincipalRepositoryInterface     $principalRepository,
        private PolicyEvaluatorInterface         $policyEvaluator,
    ) {
    }

    /**
     * @param RejectTalentInputPort $input
     * @return DraftTalent
     * @throws TalentNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function process(RejectTalentInputPort $input): DraftTalent
    {
        $talent = $this->dratTalentRepository->findById($input->talentIdentifier());

        if ($talent === null) {
            throw new TalentNotFoundException();
        }

        $principal = $this->principalRepository->findById($input->principalIdentifier());
        if ($principal === null) {
            throw new PrincipalNotFoundException();
        }
        $groupIds = array_map(
            static fn ($groupIdentifier) => (string) $groupIdentifier,
            $talent->groupIdentifiers()
        );
        $resource = new Resource(
            type: ResourceType::TALENT,
            agencyId: (string) $talent->agencyIdentifier(),
            groupIds: $groupIds,
            talentIds: [(string) $talent->talentIdentifier()],
        );

        if (! $this->policyEvaluator->evaluate($principal, Action::REJECT, $resource)) {
            throw new UnauthorizedException();
        }

        $previousStatus = $talent->status();

        if ($previousStatus !== ApprovalStatus::UnderReview) {
            throw new InvalidStatusException();
        }

        $talent->setStatus(ApprovalStatus::Rejected);

        $this->dratTalentRepository->save($talent);

        $history = $this->talentHistoryFactory->create(
            actionType: HistoryActionType::DraftStatusChange,
            editorIdentifier: $input->principalIdentifier(),
            submitterIdentifier: $talent->editorIdentifier(),
            talentIdentifier: $talent->publishedTalentIdentifier(),
            draftTalentIdentifier: $talent->talentIdentifier(),
            fromStatus: $previousStatus,
            toStatus: $talent->status(),
            fromVersion: null,
            toVersion: null,
            subjectName: $talent->name(),
        );
        $this->talentHistoryRepository->save($history);

        return $talent;
    }
}
