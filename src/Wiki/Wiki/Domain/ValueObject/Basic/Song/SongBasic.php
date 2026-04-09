<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Domain\ValueObject\Basic\Song;

use DateTimeImmutable;
use Source\Wiki\Shared\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\BasicInterface;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\Name;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

final readonly class SongBasic implements BasicInterface
{
    /**
     * @param array<WikiIdentifier> $groupIdentifiers
     * @param array<WikiIdentifier> $talentIdentifiers
     * @param array<SongGenre> $genres
     */
    public function __construct(
        // 基本情報
        private Name $name,
        private string $normalizedName,
        private ?SongType $songType,
        private array $genres,
        // 関連エンティティ
        private ?WikiIdentifier $agencyIdentifier,
        private array $groupIdentifiers,
        private array $talentIdentifiers,
        // リリース情報
        private ?ReleaseDate $releaseDate,
        private ?string $albumName,
        private ?ImageIdentifier $coverImageIdentifier,
        // クレジット情報
        private Lyricist $lyricist,
        private string $normalizedLyricist,
        private Composer $composer,
        private string $normalizedComposer,
        private Arranger $arranger,
        private string $normalizedArranger,
    ) {
    }

    public function name(): Name
    {
        return $this->name;
    }

    public function normalizedName(): string
    {
        return $this->normalizedName;
    }

    public function songType(): ?SongType
    {
        return $this->songType;
    }

    /**
     * @return array<SongGenre>
     */
    public function genres(): array
    {
        return $this->genres;
    }

    public function agencyIdentifier(): ?WikiIdentifier
    {
        return $this->agencyIdentifier;
    }

    /**
     * @return array<WikiIdentifier>
     */
    public function groupIdentifiers(): array
    {
        return $this->groupIdentifiers;
    }

    /**
     * @return array<WikiIdentifier>
     */
    public function talentIdentifiers(): array
    {
        return $this->talentIdentifiers;
    }

    public function releaseDate(): ?ReleaseDate
    {
        return $this->releaseDate;
    }

    public function albumName(): ?string
    {
        return $this->albumName;
    }

    public function coverImageIdentifier(): ?ImageIdentifier
    {
        return $this->coverImageIdentifier;
    }

    public function lyricist(): Lyricist
    {
        return $this->lyricist;
    }

    public function normalizedLyricist(): string
    {
        return $this->normalizedLyricist;
    }

    public function composer(): Composer
    {
        return $this->composer;
    }

    public function normalizedComposer(): string
    {
        return $this->normalizedComposer;
    }

    public function arranger(): Arranger
    {
        return $this->arranger;
    }

    public function normalizedArranger(): string
    {
        return $this->normalizedArranger;
    }

    public function normalizableKeys(): array
    {
        return [
            'name' => 'normalized_name',
            'lyricist' => 'normalized_lyricist',
            'composer' => 'normalized_composer',
            'arranger' => 'normalized_arranger',
        ];
    }

    public function supportsResourceType(ResourceType $resourceType): bool
    {
        return $resourceType === ResourceType::SONG;
    }

    public function getBasicType(): string
    {
        return 'song';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => $this->getBasicType(),
            // 基本情報
            'name' => (string)$this->name,
            'normalized_name' => $this->normalizedName,
            'song_type' => $this->songType?->value,
            'genres' => array_map(
                static fn (SongGenre $genre) => $genre->value,
                $this->genres
            ),
            // 関連エンティティ
            'agency_identifier' => $this->agencyIdentifier !== null ? (string)$this->agencyIdentifier : null,
            'group_identifiers' => array_map(
                static fn (WikiIdentifier $identifier) => (string)$identifier,
                $this->groupIdentifiers
            ),
            'talent_identifiers' => array_map(
                static fn (WikiIdentifier $identifier) => (string)$identifier,
                $this->talentIdentifiers
            ),
            // リリース情報
            'release_date' => $this->releaseDate?->format('Y-m-d'),
            'album_name' => $this->albumName,
            'cover_image_identifier' => $this->coverImageIdentifier !== null ? (string)$this->coverImageIdentifier : null,
            // クレジット情報
            'lyricist' => (string)$this->lyricist,
            'normalized_lyricist' => $this->normalizedLyricist,
            'composer' => (string)$this->composer,
            'normalized_composer' => $this->normalizedComposer,
            'arranger' => (string)$this->arranger,
            'normalized_arranger' => $this->normalizedArranger,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            // 基本情報
            name: new Name($data['name']),
            normalizedName: $data['normalized_name'] ?? '',
            songType: isset($data['song_type']) ? SongType::from($data['song_type']) : null,
            genres: isset($data['genres'])
                ? array_map(SongGenre::from(...), $data['genres'])
                : [],
            // 関連エンティティ
            agencyIdentifier: isset($data['agency_identifier']) ? new WikiIdentifier($data['agency_identifier']) : null,
            groupIdentifiers: isset($data['group_identifiers'])
                ? array_map(static fn (string $id) => new WikiIdentifier($id), $data['group_identifiers'])
                : [],
            talentIdentifiers: isset($data['talent_identifiers'])
                ? array_map(static fn (string $id) => new WikiIdentifier($id), $data['talent_identifiers'])
                : [],
            // リリース情報
            releaseDate: isset($data['release_date']) ? new ReleaseDate(new DateTimeImmutable($data['release_date'])) : null,
            albumName: $data['album_name'] ?? null,
            coverImageIdentifier: isset($data['cover_image_identifier']) ? new ImageIdentifier($data['cover_image_identifier']) : null,
            // クレジット情報
            lyricist: new Lyricist($data['lyricist'] ?? ''),
            normalizedLyricist: $data['normalized_lyricist'] ?? '',
            composer: new Composer($data['composer'] ?? ''),
            normalizedComposer: $data['normalized_composer'] ?? '',
            arranger: new Arranger($data['arranger'] ?? ''),
            normalizedArranger: $data['normalized_arranger'] ?? '',
        );
    }
}
