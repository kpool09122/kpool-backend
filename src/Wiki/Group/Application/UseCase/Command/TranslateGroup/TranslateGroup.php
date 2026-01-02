<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Application\UseCase\Command\TranslateGroup;

use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Group\Application\Exception\GroupNotFoundException;
use Source\Wiki\Group\Application\Service\TranslationServiceInterface;
use Source\Wiki\Group\Domain\Entity\DraftGroup;
use Source\Wiki\Group\Domain\Repository\DraftGroupRepositoryInterface;
use Source\Wiki\Group\Domain\Repository\GroupRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

readonly class TranslateGroup implements TranslateGroupInterface
{
    public function __construct(
        private GroupRepositoryInterface      $groupRepository,
        private DraftGroupRepositoryInterface $draftGroupRepository,
        private TranslationServiceInterface   $translationService,
        private PrincipalRepositoryInterface  $principalRepository,
        private PolicyEvaluatorInterface      $policyEvaluator,
    ) {
    }

    /**
     * @param TranslateGroupInputPort $input
     * @return DraftGroup[]
     * @throws GroupNotFoundException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function process(TranslateGroupInputPort $input): array
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

        if (! $this->policyEvaluator->evaluate($principal, Action::TRANSLATE, $resourceIdentifier)) {
            throw new UnauthorizedException();
        }

        $languages = Language::allExcept($group->language());

        $groupDrafts = [];
        foreach ($languages as $language) {
            // 外部翻訳サービスを使って翻訳
            $groupDraft = $this->translationService->translateGroup($group, $language);
            $groupDrafts[] = $groupDraft;
            $this->draftGroupRepository->save($groupDraft);
        }

        return $groupDrafts;
    }
}
