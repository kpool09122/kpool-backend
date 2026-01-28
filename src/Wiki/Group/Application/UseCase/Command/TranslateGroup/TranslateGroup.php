<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Application\UseCase\Command\TranslateGroup;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Group\Application\Exception\GroupNotFoundException;
use Source\Wiki\Group\Application\Service\TranslationServiceInterface;
use Source\Wiki\Group\Domain\Entity\DraftGroup;
use Source\Wiki\Group\Domain\Factory\DraftGroupFactoryInterface;
use Source\Wiki\Group\Domain\Repository\DraftGroupRepositoryInterface;
use Source\Wiki\Group\Domain\Repository\GroupRepositoryInterface;
use Source\Wiki\Group\Domain\ValueObject\Description;
use Source\Wiki\Group\Domain\ValueObject\GroupName;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\Resource;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

readonly class TranslateGroup implements TranslateGroupInterface
{
    public function __construct(
        private GroupRepositoryInterface      $groupRepository,
        private DraftGroupRepositoryInterface $draftGroupRepository,
        private TranslationServiceInterface   $translationService,
        private DraftGroupFactoryInterface    $draftGroupFactory,
        private PrincipalRepositoryInterface  $principalRepository,
        private PolicyEvaluatorInterface      $policyEvaluator,
    ) {
    }

    /**
     * @param TranslateGroupInputPort $input
     * @return DraftGroup[]
     * @throws GroupNotFoundException
     * @throws DisallowedException
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
        $resource = new Resource(
            type: ResourceType::GROUP,
            agencyId: $group->agencyIdentifier() ? (string) $group->agencyIdentifier() : null,
            groupIds: [(string) $group->groupIdentifier()],
        );

        if (! $this->policyEvaluator->evaluate($principal, Action::TRANSLATE, $resource)) {
            throw new DisallowedException();
        }

        $languages = Language::allExcept($group->language());

        $groupDrafts = [];
        $translatedAt = new DateTimeImmutable();
        foreach ($languages as $language) {
            $translatedData = $this->translationService->translateGroup($group, $language);

            $groupDraft = $this->draftGroupFactory->create(
                editorIdentifier: null,
                language: $language,
                name: new GroupName($translatedData->translatedName()),
                slug: $group->slug(),
                translationSetIdentifier: $group->translationSetIdentifier(),
            );

            $groupDraft->setDescription(new Description($translatedData->translatedDescription()));
            if ($group->agencyIdentifier() !== null) {
                $groupDraft->setAgencyIdentifier($group->agencyIdentifier());
            }
            $groupDraft->setPublishedGroupIdentifier($input->publishedGroupIdentifier() ?? $input->groupIdentifier());
            $groupDraft->setSourceEditorIdentifier($group->editorIdentifier());
            $groupDraft->setTranslatedAt($translatedAt);

            $groupDrafts[] = $groupDraft;
            $this->draftGroupRepository->save($groupDraft);
        }

        return $groupDrafts;
    }
}
