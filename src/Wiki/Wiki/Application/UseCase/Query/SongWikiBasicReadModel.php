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
        private string $songType,
        private array $genres,
        private ?string $agencyIdentifier,
        private ?string $releaseDate,
        private string $albumName,
        private ?string $coverImageIdentifier,
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
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'normalizedName' => $this->normalizedName,
            'songType' => $this->songType,
            'genres' => $this->genres,
            'agencyIdentifier' => $this->agencyIdentifier,
            'releaseDate' => $this->releaseDate,
            'albumName' => $this->albumName,
            'coverImageIdentifier' => $this->coverImageIdentifier,
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
