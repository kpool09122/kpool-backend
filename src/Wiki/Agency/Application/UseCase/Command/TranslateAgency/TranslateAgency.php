<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Application\UseCase\Command\TranslateAgency;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Agency\Application\Exception\AgencyNotFoundException;
use Source\Wiki\Agency\Application\Service\TranslationServiceInterface;
use Source\Wiki\Agency\Domain\Entity\DraftAgency;
use Source\Wiki\Agency\Domain\Factory\DraftAgencyFactoryInterface;
use Source\Wiki\Agency\Domain\Repository\AgencyRepositoryInterface;
use Source\Wiki\Agency\Domain\Repository\DraftAgencyRepositoryInterface;
use Source\Wiki\Agency\Domain\ValueObject\Description;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\Resource;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Agency\CEO;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\Name;

readonly class TranslateAgency implements TranslateAgencyInterface
{
    public function __construct(
        private AgencyRepositoryInterface      $agencyRepository,
        private DraftAgencyRepositoryInterface $draftAgencyRepository,
        private TranslationServiceInterface    $translationService,
        private DraftAgencyFactoryInterface    $draftAgencyFactory,
        private PrincipalRepositoryInterface   $principalRepository,
        private PolicyEvaluatorInterface       $policyEvaluator,
    ) {
    }

    /**
     * @param TranslateAgencyInputPort $input
     * @return DraftAgency[]
     * @throws AgencyNotFoundException
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function process(TranslateAgencyInputPort $input): array
    {
        $agency = $this->agencyRepository->findById($input->agencyIdentifier());

        if ($agency === null) {
            throw new AgencyNotFoundException();
        }

        $principal = $this->principalRepository->findById($input->principalIdentifier());
        if ($principal === null) {
            throw new PrincipalNotFoundException();
        }

        $resource = new Resource(
            type: ResourceType::AGENCY,
            agencyId: (string) $agency->agencyIdentifier(),
            groupIds: [],
        );

        if (! $this->policyEvaluator->evaluate($principal, Action::TRANSLATE, $resource)) {
            throw new DisallowedException();
        }

        $languages = Language::allExcept($agency->language());

        $agencyDrafts = [];
        $translatedAt = new DateTimeImmutable();
        foreach ($languages as $language) {
            $translatedData = $this->translationService->translateAgency($agency, $language);

            $agencyDraft = $this->draftAgencyFactory->create(
                editorIdentifier: null,
                language: $language,
                agencyName: new Name($translatedData->translatedName()),
                slug: $agency->slug(),
                translationSetIdentifier: $agency->translationSetIdentifier(),
            );

            $agencyDraft->setCEO(new CEO($translatedData->translatedCEO()));
            $agencyDraft->setDescription(new Description($translatedData->translatedDescription()));
            if ($agency->foundedIn() !== null) {
                $agencyDraft->setFoundedIn($agency->foundedIn());
            }
            $agencyDraft->setPublishedAgencyIdentifier($input->publishedAgencyIdentifier());
            $agencyDraft->setSourceEditorIdentifier($agency->editorIdentifier());
            $agencyDraft->setTranslatedAt($translatedAt);

            $agencyDrafts[] = $agencyDraft;
            $this->draftAgencyRepository->save($agencyDraft);
        }

        return $agencyDrafts;
    }
}
