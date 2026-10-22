<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Command\TranslateWiki;

use DateTimeImmutable;
use Source\Shared\Domain\Support\TypedValue;
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
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\BasicInterface;
use Source\Wiki\Wiki\Domain\ValueObject\TranslationSource;
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
        $publishedWikiIdentifierMap = [];
        $publishedWikis = $this->wikiRepository->findByTranslationSetIdentifierAndLanguages(
            $wiki->translationSetIdentifier(),
            $languages,
        );
        foreach ($publishedWikis as $publishedWiki) {
            $publishedWikiIdentifierMap[$publishedWiki->language()->value] = $publishedWiki->wikiIdentifier();
        }

        $wikiDrafts = [];
        $translatedAt = new DateTimeImmutable();
        foreach ($languages as $language) {
            $translatedData = $this->translationService->translateWiki($wiki, $language);

            $wikiDraft = $this->draftWikiFactory->create(
                editorIdentifier: $input->principalIdentifier(),
                language: $language,
                basic: $this->resolveTranslatedRelations($translatedData->translatedBasic(), $language),
                slug: $wiki->slug(),
                translationSetIdentifier: $wiki->translationSetIdentifier(),
            );

            $wikiDraft->setSections($translatedData->translatedSections());
            $wikiDraft->setThemeColor($wiki->themeColor());
            $wikiDraft->setFontStyle($wiki->fontStyle());
            $wikiDraft->setImageIdentifier($wiki->imageIdentifier());
            $wikiDraft->setTitle($translatedData->translatedTitle());
            $wikiDraft->setMetaDescription($translatedData->translatedMetaDescription());
            $wikiDraft->setKeywords($translatedData->translatedKeywords());
            $publishedWikiIdentifier = $publishedWikiIdentifierMap[$language->value] ?? null;
            if ($publishedWikiIdentifier !== null) {
                $wikiDraft->setPublishedWikiIdentifier($publishedWikiIdentifier);
            }
            $wikiDraft->setSourceEditorIdentifier($wiki->editorIdentifier());
            $wikiDraft->setTranslatedAt($translatedAt);
            $wikiDraft->setTranslationSource(new TranslationSource($wiki->wikiIdentifier(), $wiki->version()));

            $wikiDrafts[] = $wikiDraft;
            $this->draftWikiRepository->save($wikiDraft);
        }

        $output->setDraftWikis($wikiDrafts);
    }

    private function resolveTranslatedRelations(BasicInterface $basic, Language $language): BasicInterface
    {
        $data = $basic->toArray();
        $resolved = $data;
        if (array_key_exists('agency_identifier', $data)) {
            $resolved['agency_identifier'] = $this->resolveTranslatedIdentifier(TypedValue::nullableString($data['agency_identifier']), $language);
        }
        foreach (['group_identifiers', 'talent_identifiers'] as $key) {
            if (! array_key_exists($key, $data)) {
                continue;
            }
            $identifiers = [];
            foreach (TypedValue::stringArray($data[$key]) as $identifier) {
                $translatedIdentifier = $this->resolveTranslatedIdentifier($identifier, $language);
                if ($translatedIdentifier !== null) {
                    $identifiers[] = $translatedIdentifier;
                }
            }
            $resolved[$key] = array_values(array_unique($identifiers));
        }

        return $resolved === $data ? $basic : $basic::fromArray($resolved);
    }

    private function resolveTranslatedIdentifier(?string $identifier, Language $language): ?string
    {
        if ($identifier === null) {
            return null;
        }
        $wiki = $this->wikiRepository->findById(new WikiIdentifier($identifier));
        if ($wiki === null) {
            return null;
        }
        if ($wiki->language() === $language) {
            return (string) $wiki->wikiIdentifier();
        }
        $translations = $this->wikiRepository->findByTranslationSetIdentifierAndLanguages(
            $wiki->translationSetIdentifier(),
            [$language],
        );
        foreach ($translations as $translation) {
            if ($translation->language() === $language) {
                return (string) $translation->wikiIdentifier();
            }
        }

        return null;
    }
}
