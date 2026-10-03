<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query;

readonly class SongWikiBasicReadModel implements WikiBasicReadModel
{
    use ArrayAccessibleReadModel;

    /**
     * @param list<string> $genres
     * @param list<TalentWikiGroupSummaryReadModel> $groups
     * @param list<SongWikiTalentSummaryReadModel> $talents
     */
    public function __construct(
        private string $name,
        private string $normalizedName,
        private ?string $songType,
        private array $genres,
        private ?string $agencyIdentifier,
        private ?WikiAgencySummaryReadModel $agency,
        private ?string $releaseDate,
        private ?string $albumName,
        private string $lyricist,
        private string $normalizedLyricist,
        private string $composer,
        private string $normalizedComposer,
        private string $arranger,
        private string $normalizedArranger,
        private array $groups,
        private array $talents,
    ) {
    }

    /**
     * @return array{name: string, normalizedName: string, songType: string|null, genres: list<string>, agencyIdentifier: string|null, agency: array{wikiIdentifier: string, slug: string, language: string, name: string, normalizedName: string}|null, releaseDate: string|null, albumName: string|null, lyricist: string, normalizedLyricist: string, composer: string, normalizedComposer: string, arranger: string, normalizedArranger: string, groups: list<array{wikiIdentifier: string, slug: string, language: string, name: string, normalizedName: string, agencyIdentifier: string|null, groupType: string|null, status: string|null, generation: string|null, debutDate: string|null, disbandDate: string|null, fandomName: string, officialColors: list<array{colorCode: string, label: string}>, emoji: string, representativeSymbol: string}>, talents: list<array{wikiIdentifier: string, slug: string, language: string, name: string, normalizedName: string, realName: string, normalizedRealName: string, birthday: string|null, agencyIdentifier: string|null, emoji: string, representativeSymbol: string, position: string, mbti: string|null, zodiacSign: string|null, englishLevel: string|null, height: int|string|null, bloodType: string|null, fandomName: string}>}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'normalizedName' => $this->normalizedName,
            'songType' => $this->songType,
            'genres' => $this->genres,
            'agencyIdentifier' => $this->agencyIdentifier,
            'agency' => $this->agency?->toArray(),
            'releaseDate' => $this->releaseDate,
            'albumName' => $this->albumName,
            'lyricist' => $this->lyricist,
            'normalizedLyricist' => $this->normalizedLyricist,
            'composer' => $this->composer,
            'normalizedComposer' => $this->normalizedComposer,
            'arranger' => $this->arranger,
            'normalizedArranger' => $this->normalizedArranger,
            'groups' => array_map(
                static fn (TalentWikiGroupSummaryReadModel $group): array => $group->toArray(),
                $this->groups,
            ),
            'talents' => array_map(
                static fn (SongWikiTalentSummaryReadModel $talent): array => $talent->toArray(),
                $this->talents,
            ),
        ];
    }
}
