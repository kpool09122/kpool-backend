<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Application\UseCase\Command\TranslateTalent;

use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Talent\Application\Exception\TalentNotFoundException;
use Source\Wiki\Talent\Application\Service\TranslationServiceInterface;
use Source\Wiki\Talent\Domain\Entity\DraftTalent;
use Source\Wiki\Talent\Domain\Repository\TalentRepositoryInterface;

readonly class TranslateTalent implements TranslateTalentInterface
{
    public function __construct(
        private TalentRepositoryInterface    $talentRepository,
        private TranslationServiceInterface  $translationService,
        private PrincipalRepositoryInterface $principalRepository,
    ) {
    }

    /**
     * @param TranslateTalentInputPort $input
     * @return DraftTalent[]
     * @throws TalentNotFoundException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function process(TranslateTalentInputPort $input): array
    {
        $talent = $this->talentRepository->findById($input->talentIdentifier());

        if ($talent === null) {
            throw new TalentNotFoundException();
        }

        $principal = $this->principalRepository->findById($input->principalIdentifier());
        if ($principal === null) {
            throw new PrincipalNotFoundException();
        }
        $groupIds = array_map(
            fn ($groupIdentifier) => (string) $groupIdentifier,
            $talent->groupIdentifiers()
        );
        $resourceIdentifier = new ResourceIdentifier(
            type: ResourceType::TALENT,
            agencyId: (string) $talent->agencyIdentifier(),
            groupIds: $groupIds,
            talentIds: [(string) $talent->talentIdentifier()],
        );

        if (! $principal->role()->can(Action::TRANSLATE, $resourceIdentifier, $principal)) {
            throw new UnauthorizedException();
        }

        $languages = Language::allExcept($talent->language());

        $talentDrafts = [];
        foreach ($languages as $language) {
            // 外部翻訳サービスを使って翻訳
            $talentDraft = $this->translationService->translateTalent($talent, $language);
            $talentDrafts[] = $talentDraft;
            $this->talentRepository->saveDraft($talentDraft);
        }

        return $talentDrafts;
    }
}
