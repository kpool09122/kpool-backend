<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Infrastructure\Service;

use Application\Models\Wiki\Wiki as WikiModel;
use Source\Shared\Domain\Support\TypedValue;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\VideoLink\Application\Service\VideoTitleNameQueryServiceInterface;
use Source\Wiki\Wiki\Domain\Entity\Wiki;

readonly class VideoTitleNameQueryService implements VideoTitleNameQueryServiceInterface
{
    public function findNames(Wiki $wiki): array
    {
        $translationSetIdentifier = (string) $wiki->translationSetIdentifier();
        $sourceIds = WikiModel::query()->where('translation_set_identifier', $translationSetIdentifier)->pluck('id')->all();
        $relations = match ($wiki->resourceType()) {
            ResourceType::GROUP => [
                ['wiki_talent_basic_groups', 'group_identifier', 'wiki_id'],
                ['wiki_song_basic_groups', 'group_identifier', 'wiki_id'],
            ],
            ResourceType::TALENT => [
                ['wiki_talent_basic_groups', 'wiki_id', 'group_identifier'],
                ['wiki_song_basic_talents', 'talent_identifier', 'wiki_id'],
            ],
            ResourceType::SONG => [
                ['wiki_song_basic_groups', 'wiki_id', 'group_identifier'],
                ['wiki_song_basic_talents', 'wiki_id', 'talent_identifier'],
            ],
            default => [],
        };

        $translationSetIdentifiers = [$translationSetIdentifier];
        foreach ($relations as [$table, $sourceColumn, $targetColumn]) {
            $relatedTranslationSets = WikiModel::query()
                ->join($table, 'wikis.id', '=', $table . '.' . $targetColumn)
                ->whereIn($table . '.' . $sourceColumn, $sourceIds)
                ->pluck('wikis.translation_set_identifier')
                ->map(static fn (mixed $value): string => TypedValue::string($value))->all();
            $translationSetIdentifiers = [...$translationSetIdentifiers, ...$relatedTranslationSets];
        }

        $names = [$wiki->language()->value => [(string) $wiki->basic()->name()]];
        // Read only names, across translations of the source and its direct relations.
        // Query count is independent of the number of related profiles.
        foreach (['wiki_group_basics', 'wiki_talent_basics', 'wiki_song_basics'] as $table) {
            $profiles = WikiModel::query()->join($table, 'wikis.id', '=', $table . '.wiki_id')
                ->whereIn('wikis.translation_set_identifier', array_unique($translationSetIdentifiers))
                ->select('wikis.language', $table . '.name')->get();
            foreach ($profiles as $profile) {
                $names[TypedValue::string($profile->language)][] = TypedValue::string($profile->getAttribute('name'));
            }
        }

        return array_map(static fn (array $values): array => array_values(array_unique($values)), $names);
    }
}
