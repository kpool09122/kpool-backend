<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Application\UseCase\Command\PublishGroup;

use Source\Wiki\Group\Application\Exception\ExistsApprovedButNotTranslatedGroupException;
use Source\Wiki\Group\Application\Exception\GroupNotFoundException;
use Source\Wiki\Group\Domain\Entity\Group;
use Source\Wiki\Group\Domain\Factory\GroupFactoryInterface;
use Source\Wiki\Group\Domain\Factory\GroupHistoryFactoryInterface;
use Source\Wiki\Group\Domain\Factory\GroupSnapshotFactoryInterface;
use Source\Wiki\Group\Domain\Repository\DraftGroupRepositoryInterface;
use Source\Wiki\Group\Domain\Repository\GroupHistoryRepositoryInterface;
use Source\Wiki\Group\Domain\Repository\GroupRepositoryInterface;
use Source\Wiki\Group\Domain\Repository\GroupSnapshotRepositoryInterface;
use Source\Wiki\Group\Domain\Service\GroupServiceInterface;
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

readonly class PublishGroup implements PublishGroupInterface
{
    public function __construct(
        private GroupRepositoryInterface         $groupRepository,
        private GroupServiceInterface            $groupService,
        private GroupFactoryInterface            $groupFactory,
        private GroupHistoryRepositoryInterface  $groupHistoryRepository,
        private GroupHistoryFactoryInterface     $groupHistoryFactory,
        private GroupSnapshotFactoryInterface    $groupSnapshotFactory,
        private GroupSnapshotRepositoryInterface $groupSnapshotRepository,
        private PrincipalRepositoryInterface     $principalRepository,
        private DraftGroupRepositoryInterface    $draftGroupRepository,
        private PolicyEvaluatorInterface         $policyEvaluator,
    ) {
    }

    /**
     * @param PublishGroupInputPort $input
     * @return Group
     * @throws GroupNotFoundException
     * @throws InvalidStatusException
     * @throws ExistsApprovedButNotTranslatedGroupException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function process(PublishGroupInputPort $input): Group
    {
        $group = $this->draftGroupRepository->findById($input->groupIdentifier());

        if ($group === null) {
            throw new GroupNotFoundException();
        }

        if ($group->status() !== ApprovalStatus::UnderReview) {
            throw new InvalidStatusException();
        }

        $principal = $this->principalRepository->findById($input->principalIdentifier());
        if ($principal === null) {
            throw new PrincipalNotFoundException();
        }
        $resource = new Resource(
            type: ResourceType::GROUP,
            agencyId: $group->agencyIdentifier() ? (string) $group->agencyIdentifier() : null,
            groupIds: [(string) $group->groupIdentifier()],
        );

        if (! $this->policyEvaluator->evaluate($principal, Action::PUBLISH, $resource)) {
            throw new UnauthorizedException();
        }

        // 同じ翻訳セットの別版で承認済みがあるかチェック
        if ($this->groupService->existsApprovedButNotTranslatedGroup(
            $group->translationSetIdentifier(),
            $group->groupIdentifier(),
        )) {
            throw new ExistsApprovedButNotTranslatedGroupException();
        }

        if ($group->publishedGroupIdentifier()) {
            $publishedGroup = $this->groupRepository->findById($input->publishedGroupIdentifier());
            if ($publishedGroup === null) {
                throw new GroupNotFoundException();
            }

            // スナップショット保存（更新前の状態を保存）
            $snapshot = $this->groupSnapshotFactory->create($publishedGroup);
            $this->groupSnapshotRepository->save($snapshot);

            $publishedGroup->setName($group->name());
            $publishedGroup->setNormalizedName($group->normalizedName());
            $publishedGroup->updateVersion();
        } else {
            $publishedGroup = $this->groupFactory->create(
                $group->translationSetIdentifier(),
                $group->language(),
                $group->name(),
            );
        }
        if ($group->agencyIdentifier()) {
            $publishedGroup->setAgencyIdentifier($group->agencyIdentifier());
        }
        $publishedGroup->setDescription($group->description());

        $this->groupRepository->save($publishedGroup);

        $history = $this->groupHistoryFactory->create(
            actionType: HistoryActionType::Publish,
            editorIdentifier: $input->principalIdentifier(),
            submitterIdentifier: $group->editorIdentifier(),
            groupIdentifier: $group->publishedGroupIdentifier(),
            draftGroupIdentifier: $group->groupIdentifier(),
            fromStatus: $group->status(),
            toStatus: null,
            fromVersion: null,
            toVersion: null,
            subjectName: $group->name(),
        );
        $this->groupHistoryRepository->save($history);

        $this->draftGroupRepository->delete($group);

        return $publishedGroup;
    }
}
