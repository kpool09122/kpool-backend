<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Command\TranslateWiki;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\Resource;
use Source\Wiki\Wiki\Application\Exception\WikiNotFoundException;
use Source\Wiki\Wiki\Application\Service\TranslationServiceInterface;
use Source\Wiki\Wiki\Domain\Factory\DraftWikiFactoryInterface;
use Source\Wiki\Wiki\Domain\Repository\DraftWikiRepositoryInterface;
use Source\Wiki\Wiki\Domain\Repository\WikiRepositoryInterface;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

readonly class TranslateWiki implements TranslateWikiInterface
{
    public function __construct(
        private WikiRepositoryInterface      $wikiRepository,
        private DraftWikiRepositoryInterface $draftWikiRepository,
        private TranslationServiceInterface  $translationService,
        private DraftWikiFactoryInterface    $draftWikiFactory,
        private PrincipalRepositoryInterface $principalRepository,
        private PolicyEvaluatorInterface     $policyEvaluator,
    ) {
    }

    /**
     * @param TranslateWikiInputPort $input
     * @param TranslateWikiOutputPort $output
     * @return void
     * @throws WikiNotFoundException
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function process(TranslateWikiInputPort $input, TranslateWikiOutputPort $output): void
    {
        $wiki = $this->wikiRepository->findById($input->wikiIdentifier());

        if ($wiki === null) {
            throw new WikiNotFoundException();
        }

        $principal = $this->principalRepository->findById($input->principalIdentifier());
        if ($principal === null) {
            throw new PrincipalNotFoundException();
        }

        $resource = new Resource(
            type: $input->resourceType(),
            agencyId: $input->agencyIdentifier() ? (string) $input->agencyIdentifier() : null,
            groupIds: array_map(
                static fn (WikiIdentifier $id) => (string) $id,
                $input->groupIdentifiers(),
            ),
            talentIds: array_map(
                static fn (WikiIdentifier $id) => (string) $id,
                $input->talentIdentifiers(),
            ),
        );

        if (! $this->policyEvaluator->evaluate($principal, Action::TRANSLATE, $resource)) {
            throw new DisallowedException();
        }

        $languages = Language::allExcept($wiki->language());

        $wikiDrafts = [];
        $translatedAt = new DateTimeImmutable();
        foreach ($languages as $language) {
            $translatedData = $this->translationService->translateWiki($wiki, $language);

            $wikiDraft = $this->draftWikiFactory->create(
                editorIdentifier: null,
                language: $language,
                resourceType: $wiki->resourceType(),
                basic: $translatedData->translatedBasic(),
                slug: $wiki->slug(),
                translationSetIdentifier: $wiki->translationSetIdentifier(),
            );

            $wikiDraft->setSections($translatedData->translatedSections());
            $wikiDraft->setThemeColor($wiki->themeColor());
            $wikiDraft->setPublishedWikiIdentifier($input->wikiIdentifier());
            $wikiDraft->setSourceEditorIdentifier($wiki->editorIdentifier());
            $wikiDraft->setTranslatedAt($translatedAt);

            $wikiDrafts[] = $wikiDraft;
            $this->draftWikiRepository->save($wikiDraft);
        }

        $output->setDraftWikis($wikiDrafts);
    }
}
