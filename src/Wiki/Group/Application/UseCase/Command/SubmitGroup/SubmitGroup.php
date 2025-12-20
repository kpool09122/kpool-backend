<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Application\UseCase\Command\SubmitGroup;

use Source\Wiki\Group\Application\Exception\GroupNotFoundException;
use Source\Wiki\Group\Domain\Entity\DraftGroup;
use Source\Wiki\Group\Domain\Factory\GroupHistoryFactoryInterface;
use Source\Wiki\Group\Domain\Repository\GroupHistoryRepositoryInterface;
use Source\Wiki\Group\Domain\Repository\GroupRepositoryInterface;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

readonly class SubmitGroup implements SubmitGroupInterface
{
    public function __construct(
        private GroupRepositoryInterface $groupRepository,
        private GroupHistoryRepositoryInterface $groupHistoryRepository,
        private GroupHistoryFactoryInterface    $groupHistoryFactory,
    ) {
    }

    /**
     * @param SubmitGroupInputPort $input
     * @return DraftGroup
     * @throws GroupNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     */
    public function process(SubmitGroupInputPort $input): DraftGroup
    {
        $group = $this->groupRepository->findDraftById($input->groupIdentifier());

        if ($group === null) {
            throw new GroupNotFoundException();
        }

        $principal = $input->principal();
        $resourceIdentifier = new ResourceIdentifier(
            type: ResourceType::GROUP,
            agencyId: $group->agencyIdentifier() ? (string) $group->agencyIdentifier() : null,
            groupIds: [(string) $group->groupIdentifier()],
        );

        if (! $principal->role()->can(Action::SUBMIT, $resourceIdentifier, $principal)) {
            throw new UnauthorizedException();
        }

        if ($group->status() !== ApprovalStatus::Pending
        && $group->status() !== ApprovalStatus::Rejected) {
            throw new InvalidStatusException();
        }

        $previousStatus = $group->status();
        $group->setStatus(ApprovalStatus::UnderReview);

        $this->groupRepository->saveDraft($group);

        $history = $this->groupHistoryFactory->create(
            new EditorIdentifier((string)$input->principal()->principalIdentifier()),
            $group->editorIdentifier(),
            $group->publishedGroupIdentifier(),
            $group->groupIdentifier(),
            $previousStatus,
            $group->status(),
            $group->name(),
        );
        $this->groupHistoryRepository->save($history);

        return $group;
    }
}
