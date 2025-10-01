<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Application\UseCase\Command\TranslateGroup;

use Source\Shared\Domain\ValueObject\Translation;
use Source\Wiki\Group\Application\Exception\GroupNotFoundException;
use Source\Wiki\Group\Application\Service\TranslationServiceInterface;
use Source\Wiki\Group\Domain\Entity\DraftGroup;
use Source\Wiki\Group\Domain\Repository\GroupRepositoryInterface;

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
     */
    public function process(TranslateGroupInputPort $input): array
    {
        $group = $this->groupRepository->findById($input->groupIdentifier());

        if ($group === null) {
            throw new GroupNotFoundException();
        }

        $translations = Translation::allExcept($group->translation());

        $groupDrafts = [];
        foreach ($translations as $translation) {
            // 外部翻訳サービスを使って翻訳
            $groupDraft = $this->translationService->translateGroup($group, $translation);
            $groupDrafts[] = $groupDraft;
            $this->groupRepository->saveDraft($groupDraft);
        }

        return $groupDrafts;
    }
}
