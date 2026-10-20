<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\Service;

use Source\Shared\Domain\Support\TypedValue;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Wiki\Domain\Repository\WikiRepositoryInterface;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\BasicInterface;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

readonly class TranslatedWikiRelationResolver
{
    public function __construct(private WikiRepositoryInterface $wikiRepository)
    {
    }

    public function resolve(BasicInterface $basic, Language $language): BasicInterface
    {
        $data = $basic->toArray();
        $resolved = $data;
        if (array_key_exists('agency_identifier', $data)) {
            $resolved['agency_identifier'] = $this->resolveIdentifier(TypedValue::nullableString($data['agency_identifier']), $language);
        }
        foreach (['group_identifiers', 'talent_identifiers'] as $key) {
            if (! array_key_exists($key, $data)) {
                continue;
            }
            $identifiers = [];
            foreach (TypedValue::stringArray($data[$key]) as $identifier) {
                $translatedIdentifier = $this->resolveIdentifier($identifier, $language);
                if ($translatedIdentifier !== null) {
                    $identifiers[] = $translatedIdentifier;
                }
            }
            $resolved[$key] = array_values(array_unique($identifiers));
        }

        return $resolved === $data ? $basic : $basic::fromArray($resolved);
    }

    private function resolveIdentifier(?string $identifier, Language $language): ?string
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
