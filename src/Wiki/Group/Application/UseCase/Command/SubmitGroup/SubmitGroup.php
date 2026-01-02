<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Application\UseCase\Command\SubmitGroup;

use Source\Wiki\Group\Application\Exception\GroupNotFoundException;
use Source\Wiki\Group\Domain\Entity\DraftGroup;
use Source\Wiki\Group\Domain\Factory\GroupHistoryFactoryInterface;
use Source\Wiki\Group\Domain\Repository\DraftGroupRepositoryInterface;
use Source\Wiki\Group\Domain\Repository\GroupHistoryRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\HistoryActionType;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

readonly class SubmitGroup implements SubmitGroupInterface
{
    public function __construct(
        private DraftGroupRepositoryInterface   $groupRepository,
        private GroupHistoryRepositoryInterface $groupHistoryRepository,
        private GroupHistoryFactoryInterface    $groupHistoryFactory,
        private PrincipalRepositoryInterface    $principalRepository,
        private PolicyEvaluatorInterface        $policyEvaluator,
    ) {
    }

    /**
     * @param SubmitGroupInputPort $input
     * @return DraftGroup
     * @throws GroupNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function process(SubmitGroupInputPort $input): DraftGroup
    {
        $group = $this->groupRepository->findById($input->groupIdentifier());

        if ($group === null) {
            throw new GroupNotFoundException();
        }

        $principal = $this->principalRepository->findById($input->principalIdentifier());
        if ($principal === null) {
            throw new PrincipalNotFoundException();
        }
        $resourceIdentifier = new ResourceIdentifier(
            type: ResourceType::GROUP,
            agencyId: $group->agencyIdentifier() ? (string) $group->agencyIdentifier() : null,
            groupIds: [(string) $group->groupIdentifier()],
        );

        if (! $this->policyEvaluator->evaluate($principal, Action::SUBMIT, $resourceIdentifier)) {
            throw new UnauthorizedException();
        }

        if ($group->status() !== ApprovalStatus::Pending
        && $group->status() !== ApprovalStatus::Rejected) {
            throw new InvalidStatusException();
        }

        $previousStatus = $group->status();
        $group->setStatus(ApprovalStatus::UnderReview);

        $this->groupRepository->save($group);

        $history = $this->groupHistoryFactory->create(
            actionType: HistoryActionType::DraftStatusChange,
            editorIdentifier: $input->principalIdentifier(),
            submitterIdentifier: $group->editorIdentifier(),
            groupIdentifier: $group->publishedGroupIdentifier(),
            draftGroupIdentifier: $group->groupIdentifier(),
            fromStatus: $previousStatus,
            toStatus: $group->status(),
            fromVersion: null,
            toVersion: null,
            subjectName: $group->name(),
        );
        $this->groupHistoryRepository->save($history);

        return $group;
    }
}
