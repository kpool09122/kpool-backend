<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Application\UseCase\Command\TranslateGroup;

use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Group\Application\Exception\GroupNotFoundException;
use Source\Wiki\Group\Application\Service\TranslationServiceInterface;
use Source\Wiki\Group\Domain\Entity\DraftGroup;
use Source\Wiki\Group\Domain\Repository\GroupRepositoryInterface;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

class TranslateGroup implements TranslateGroupInterface
{
    public function __construct(
        private GroupRepositoryInterface $groupRepository,
        private TranslationServiceInterface $translationService,
    ) {
    }

    /**
     * @param TranslateGroupInputPort $input
     * @return DraftGroup[]
     * @throws GroupNotFoundException
     * @throws UnauthorizedException
     */
    public function process(TranslateGroupInputPort $input): array
    {
        $group = $this->groupRepository->findById($input->groupIdentifier());

        if ($group === null) {
            throw new GroupNotFoundException();
        }

        $principal = $input->principal();
        $resourceIdentifier = new ResourceIdentifier(
            type: ResourceType::GROUP,
            agencyId: $group->agencyIdentifier() ? (string) $group->agencyIdentifier() : null,
            groupIds: [(string) $group->groupIdentifier()],
        );

        if (! $principal->role()->can(Action::TRANSLATE, $resourceIdentifier, $principal)) {
            throw new UnauthorizedException();
        }

        $languages = Language::allExcept($group->language());

        $groupDrafts = [];
        foreach ($languages as $language) {
            // 外部翻訳サービスを使って翻訳
            $groupDraft = $this->translationService->translateGroup($group, $language);
            $groupDrafts[] = $groupDraft;
            $this->groupRepository->saveDraft($groupDraft);
        }

        return $groupDrafts;
    }
}
