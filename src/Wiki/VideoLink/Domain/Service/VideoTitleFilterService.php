<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Domain\Service;

use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\Service\NormalizationServiceInterface;

final readonly class VideoTitleFilterService implements VideoTitleFilterServiceInterface
{
    public function __construct(
        private NormalizationServiceInterface $normalizationService,
    ) {
    }

    public function filter(array $titles, array $namesByLanguage): array
    {
        $normalizedNames = [];
        $originalNames = [];
        foreach ($namesByLanguage as $languageCode => $names) {
            $language = Language::from($languageCode);
            foreach ($names as $name) {
                if (trim($name) !== '') {
                    $originalNames[] = $name;
                }
                $normalized = trim($this->normalizationService->normalize($name, $language));
                if ($normalized !== '') {
                    $normalizedNames[$languageCode][] = $normalized;
                }
            }
        }
        $originalNames = array_values(array_unique($originalNames));
        $normalizedNames = array_map(static fn (array $names): array => array_values(array_unique($names)), $normalizedNames);

        return array_filter($titles, fn (string $title): bool => $this->matches($title, $originalNames, $normalizedNames));
    }

    /**
     * @param list<string> $originalNames
     * @param array<string, list<string>> $normalizedNames
     */
    private function matches(string $title, array $originalNames, array $normalizedNames): bool
    {
        foreach ($originalNames as $name) {
            if (str_contains($title, $name)) {
                return true;
            }
        }

        foreach ($normalizedNames as $languageCode => $names) {
            $normalizedTitle = $this->normalizationService->normalize($title, Language::from($languageCode));
            foreach ($names as $name) {
                if (str_contains($normalizedTitle, $name)) {
                    return true;
                }
            }
        }

        return false;
    }
}
