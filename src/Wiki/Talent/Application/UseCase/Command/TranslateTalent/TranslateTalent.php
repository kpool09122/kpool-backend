<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Application\UseCase\Command\TranslateTalent;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\Resource;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Talent\Application\Exception\TalentNotFoundException;
use Source\Wiki\Talent\Application\Service\TranslationServiceInterface;
use Source\Wiki\Talent\Domain\Entity\DraftTalent;
use Source\Wiki\Talent\Domain\Factory\DraftTalentFactoryInterface;
use Source\Wiki\Talent\Domain\Repository\DraftTalentRepositoryInterface;
use Source\Wiki\Talent\Domain\Repository\TalentRepositoryInterface;
use Source\Wiki\Talent\Domain\ValueObject\Career;
use Source\Wiki\Talent\Domain\ValueObject\TalentName;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Talent\RealName;

readonly class TranslateTalent implements TranslateTalentInterface
{
    public function __construct(
        private TalentRepositoryInterface      $talentRepository,
        private DraftTalentRepositoryInterface $draftTalentRepository,
        private TranslationServiceInterface    $translationService,
        private DraftTalentFactoryInterface    $draftTalentFactory,
        private PrincipalRepositoryInterface   $principalRepository,
        private PolicyEvaluatorInterface       $policyEvaluator,
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
            static fn ($groupIdentifier) => (string) $groupIdentifier,
            $talent->groupIdentifiers()
        );
        $resource = new Resource(
            type: ResourceType::TALENT,
            agencyId: (string) $talent->agencyIdentifier(),
            groupIds: $groupIds,
            talentIds: [(string) $talent->talentIdentifier()],
        );

        if (! $this->policyEvaluator->evaluate($principal, Action::TRANSLATE, $resource)) {
            throw new UnauthorizedException();
        }

        $languages = Language::allExcept($talent->language());

        $talentDrafts = [];
        $translatedAt = new DateTimeImmutable();
        foreach ($languages as $language) {
            $translatedData = $this->translationService->translateTalent($talent, $language);

            $talentDraft = $this->draftTalentFactory->create(
                editorIdentifier: null,
                slug: $talent->slug(),
                language: $language,
                name: new TalentName($translatedData->translatedName()),
                translationSetIdentifier: $talent->translationSetIdentifier(),
            );

            $talentDraft->setRealName(new RealName($translatedData->translatedRealName()));
            $talentDraft->setCareer(new Career($translatedData->translatedCareer()));
            if ($talent->agencyIdentifier() !== null) {
                $talentDraft->setAgencyIdentifier($talent->agencyIdentifier());
            }
            $talentDraft->setGroupIdentifiers($talent->groupIdentifiers());
            if ($talent->birthday() !== null) {
                $talentDraft->setBirthday($talent->birthday());
            }
            $talentDraft->setPublishedTalentIdentifier($input->publishedTalentIdentifier() ?? $input->talentIdentifier());
            $talentDraft->setSourceEditorIdentifier($talent->editorIdentifier());
            $talentDraft->setTranslatedAt($translatedAt);

            $talentDrafts[] = $talentDraft;
            $this->draftTalentRepository->save($talentDraft);
        }

        return $talentDrafts;
    }
}
