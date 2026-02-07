<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Domain\ValueObject\Basic\Song;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Shared\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\Name;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Song\Arranger;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Song\Composer;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Song\Lyricist;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Song\ReleaseDate;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Song\SongBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Song\SongGenre;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Song\SongType;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Tests\Helper\StrTestHelper;

class SongBasicTest extends TestCase
{
    /**
     * 正常系: 全ての値を指定してインスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $testData = $this->createDummySongBasic();

        $this->assertSame((string) $testData->name, (string) $testData->songBasic->name());
        $this->assertSame($testData->normalizedName, $testData->songBasic->normalizedName());
        $this->assertSame($testData->songType, $testData->songBasic->songType());
        $this->assertSame($testData->genres, $testData->songBasic->genres());
        $this->assertSame($testData->agencyIdentifier, $testData->songBasic->agencyIdentifier());
        $this->assertSame($testData->groupIdentifiers, $testData->songBasic->groupIdentifiers());
        $this->assertSame($testData->talentIdentifiers, $testData->songBasic->talentIdentifiers());
        $this->assertSame($testData->releaseDate, $testData->songBasic->releaseDate());
        $this->assertSame($testData->albumName, $testData->songBasic->albumName());
        $this->assertSame($testData->coverImageIdentifier, $testData->songBasic->coverImageIdentifier());
        $this->assertSame((string) $testData->lyricist, (string) $testData->songBasic->lyricist());
        $this->assertSame($testData->normalizedLyricist, $testData->songBasic->normalizedLyricist());
        $this->assertSame((string) $testData->composer, (string) $testData->songBasic->composer());
        $this->assertSame($testData->normalizedComposer, $testData->songBasic->normalizedComposer());
        $this->assertSame((string) $testData->arranger, (string) $testData->songBasic->arranger());
        $this->assertSame($testData->normalizedArranger, $testData->songBasic->normalizedArranger());
    }

    /**
     * 正常系: nullable値をnullで生成できること
     *
     * @return void
     */
    public function test__constructWithNullValues(): void
    {
        $testData = $this->createDummySongBasic(
            withNullableValues: false,
        );

        $this->assertNull($testData->songBasic->songType());
        $this->assertEmpty($testData->songBasic->genres());
        $this->assertNull($testData->songBasic->agencyIdentifier());
        $this->assertEmpty($testData->songBasic->groupIdentifiers());
        $this->assertEmpty($testData->songBasic->talentIdentifiers());
        $this->assertNull($testData->songBasic->releaseDate());
        $this->assertNull($testData->songBasic->albumName());
        $this->assertNull($testData->songBasic->coverImageIdentifier());
    }

    /**
     * 正常系: supportsResourceTypeがSONGでtrueを返すこと
     *
     * @return void
     */
    public function testSupportsResourceTypeWithSong(): void
    {
        $testData = $this->createDummySongBasic();

        $this->assertTrue($testData->songBasic->supportsResourceType(ResourceType::SONG));
    }

    /**
     * 正常系: supportsResourceTypeがSONG以外でfalseを返すこと
     *
     * @return void
     */
    public function testSupportsResourceTypeWithOtherTypes(): void
    {
        $testData = $this->createDummySongBasic();

        $this->assertFalse($testData->songBasic->supportsResourceType(ResourceType::AGENCY));
        $this->assertFalse($testData->songBasic->supportsResourceType(ResourceType::GROUP));
        $this->assertFalse($testData->songBasic->supportsResourceType(ResourceType::TALENT));
    }

    /**
     * 正常系: getBasicTypeがsongを返すこと
     *
     * @return void
     */
    public function testGetBasicType(): void
    {
        $testData = $this->createDummySongBasic();

        $this->assertSame('song', $testData->songBasic->getBasicType());
    }

    /**
     * 正常系: normalizableKeysが正しいキーを返すこと
     *
     * @return void
     */
    public function testNormalizableKeys(): void
    {
        $testData = $this->createDummySongBasic();

        $this->assertSame([
            'name' => 'normalized_name',
            'lyricist' => 'normalized_lyricist',
            'composer' => 'normalized_composer',
            'arranger' => 'normalized_arranger',
        ], $testData->songBasic->normalizableKeys());
    }

    /**
     * 正常系: toArrayが正しい配列を返すこと
     *
     * @return void
     */
    public function testToArray(): void
    {
        $testData = $this->createDummySongBasic();

        $array = $testData->songBasic->toArray();

        // 基本情報
        $this->assertSame('song', $array['type']);
        $this->assertSame((string) $testData->name, $array['name']);
        $this->assertSame($testData->normalizedName, $array['normalized_name']);
        $this->assertSame($testData->songType->value, $array['song_type']);
        $this->assertCount(count($testData->genres), $array['genres']);
        // 関連エンティティ
        $this->assertSame((string) $testData->agencyIdentifier, $array['agency_identifier']);
        $this->assertCount(count($testData->groupIdentifiers), $array['group_identifiers']);
        $this->assertCount(count($testData->talentIdentifiers), $array['talent_identifiers']);
        // リリース情報
        $this->assertSame($testData->releaseDate->format('Y-m-d'), $array['release_date']);
        $this->assertSame($testData->albumName, $array['album_name']);
        $this->assertSame((string) $testData->coverImageIdentifier, $array['cover_image_identifier']);
        // クレジット情報
        $this->assertSame((string) $testData->lyricist, $array['lyricist']);
        $this->assertSame($testData->normalizedLyricist, $array['normalized_lyricist']);
        $this->assertSame((string) $testData->composer, $array['composer']);
        $this->assertSame($testData->normalizedComposer, $array['normalized_composer']);
        $this->assertSame((string) $testData->arranger, $array['arranger']);
        $this->assertSame($testData->normalizedArranger, $array['normalized_arranger']);
    }

    /**
     * 正常系: toArrayでnullable値がnullの場合
     *
     * @return void
     */
    public function testToArrayWithNullValues(): void
    {
        $testData = $this->createDummySongBasic(withNullableValues: false);

        $array = $testData->songBasic->toArray();

        $this->assertNull($array['song_type']);
        $this->assertEmpty($array['genres']);
        $this->assertNull($array['agency_identifier']);
        $this->assertEmpty($array['group_identifiers']);
        $this->assertEmpty($array['talent_identifiers']);
        $this->assertNull($array['release_date']);
        $this->assertNull($array['album_name']);
        $this->assertNull($array['cover_image_identifier']);
    }

    /**
     * 正常系: fromArrayで正しくインスタンスが生成されること
     *
     * @return void
     */
    public function testFromArray(): void
    {
        $agencyUuid = StrTestHelper::generateUuid();
        $groupUuid1 = StrTestHelper::generateUuid();
        $groupUuid2 = StrTestHelper::generateUuid();
        $talentUuid1 = StrTestHelper::generateUuid();
        $coverImageUuid = StrTestHelper::generateUuid();

        $data = [
            // 基本情報
            'name' => 'Dynamite',
            'normalized_name' => 'dynamite',
            'song_type' => 'title_track',
            'genres' => ['pop', 'dance'],
            // 関連エンティティ
            'agency_identifier' => $agencyUuid,
            'group_identifiers' => [$groupUuid1, $groupUuid2],
            'talent_identifiers' => [$talentUuid1],
            // リリース情報
            'release_date' => '2020-08-21',
            'album_name' => 'BE',
            'cover_image_identifier' => $coverImageUuid,
            // クレジット情報
            'lyricist' => 'David Stewart',
            'normalized_lyricist' => 'davidstewart',
            'composer' => 'David Stewart',
            'normalized_composer' => 'davidstewart',
            'arranger' => 'David Stewart',
            'normalized_arranger' => 'davidstewart',
        ];

        $songBasic = SongBasic::fromArray($data);

        // 基本情報
        $this->assertSame('Dynamite', (string) $songBasic->name());
        $this->assertSame('dynamite', $songBasic->normalizedName());
        $this->assertSame(SongType::TITLE_TRACK, $songBasic->songType());
        $this->assertCount(2, $songBasic->genres());
        $this->assertSame(SongGenre::POP, $songBasic->genres()[0]);
        $this->assertSame(SongGenre::DANCE, $songBasic->genres()[1]);
        // 関連エンティティ
        $this->assertSame($agencyUuid, (string) $songBasic->agencyIdentifier());
        $this->assertCount(2, $songBasic->groupIdentifiers());
        $this->assertSame($groupUuid1, (string) $songBasic->groupIdentifiers()[0]);
        $this->assertSame($groupUuid2, (string) $songBasic->groupIdentifiers()[1]);
        $this->assertCount(1, $songBasic->talentIdentifiers());
        $this->assertSame($talentUuid1, (string) $songBasic->talentIdentifiers()[0]);
        // リリース情報
        $this->assertSame('2020-08-21', $songBasic->releaseDate()->format('Y-m-d'));
        $this->assertSame('BE', $songBasic->albumName());
        $this->assertSame($coverImageUuid, (string) $songBasic->coverImageIdentifier());
        // クレジット情報
        $this->assertSame('David Stewart', (string) $songBasic->lyricist());
        $this->assertSame('davidstewart', $songBasic->normalizedLyricist());
        $this->assertSame('David Stewart', (string) $songBasic->composer());
        $this->assertSame('davidstewart', $songBasic->normalizedComposer());
        $this->assertSame('David Stewart', (string) $songBasic->arranger());
        $this->assertSame('davidstewart', $songBasic->normalizedArranger());
    }

    /**
     * 正常系: fromArrayで最小限のデータで生成できること
     *
     * @return void
     */
    public function testFromArrayWithMinimalData(): void
    {
        $data = [
            'name' => 'Dynamite',
        ];

        $songBasic = SongBasic::fromArray($data);

        // 基本情報
        $this->assertSame('Dynamite', (string) $songBasic->name());
        $this->assertSame('', $songBasic->normalizedName());
        $this->assertNull($songBasic->songType());
        $this->assertEmpty($songBasic->genres());
        // 関連エンティティ
        $this->assertNull($songBasic->agencyIdentifier());
        $this->assertEmpty($songBasic->groupIdentifiers());
        $this->assertEmpty($songBasic->talentIdentifiers());
        // リリース情報
        $this->assertNull($songBasic->releaseDate());
        $this->assertNull($songBasic->albumName());
        $this->assertNull($songBasic->coverImageIdentifier());
        // クレジット情報
        $this->assertSame('', (string) $songBasic->lyricist());
        $this->assertSame('', $songBasic->normalizedLyricist());
        $this->assertSame('', (string) $songBasic->composer());
        $this->assertSame('', $songBasic->normalizedComposer());
        $this->assertSame('', (string) $songBasic->arranger());
        $this->assertSame('', $songBasic->normalizedArranger());
    }

    private function createDummySongBasic(
        bool $withNullableValues = true,
    ): SongBasicTestData {
        // 基本情報
        $name = new Name('Dynamite');
        $normalizedName = 'dynamite';
        $songType = $withNullableValues ? SongType::TITLE_TRACK : null;
        $genres = $withNullableValues ? [SongGenre::POP, SongGenre::DANCE] : [];

        // 関連エンティティ
        $agencyIdentifier = $withNullableValues ? new WikiIdentifier(StrTestHelper::generateUuid()) : null;
        $groupIdentifiers = $withNullableValues ? [
            new WikiIdentifier(StrTestHelper::generateUuid()),
        ] : [];
        $talentIdentifiers = $withNullableValues ? [
            new WikiIdentifier(StrTestHelper::generateUuid()),
            new WikiIdentifier(StrTestHelper::generateUuid()),
        ] : [];

        // リリース情報
        $releaseDate = $withNullableValues ? new ReleaseDate(new DateTimeImmutable('2020-08-21')) : null;
        $albumName = $withNullableValues ? 'BE' : null;
        $coverImageIdentifier = $withNullableValues ? new ImageIdentifier(StrTestHelper::generateUuid()) : null;

        // クレジット情報
        $lyricist = new Lyricist('David Stewart');
        $normalizedLyricist = 'davidstewart';
        $composer = new Composer('David Stewart');
        $normalizedComposer = 'davidstewart';
        $arranger = new Arranger('David Stewart');
        $normalizedArranger = 'davidstewart';

        $songBasic = new SongBasic(
            name: $name,
            normalizedName: $normalizedName,
            songType: $songType,
            genres: $genres,
            agencyIdentifier: $agencyIdentifier,
            groupIdentifiers: $groupIdentifiers,
            talentIdentifiers: $talentIdentifiers,
            releaseDate: $releaseDate,
            albumName: $albumName,
            coverImageIdentifier: $coverImageIdentifier,
            lyricist: $lyricist,
            normalizedLyricist: $normalizedLyricist,
            composer: $composer,
            normalizedComposer: $normalizedComposer,
            arranger: $arranger,
            normalizedArranger: $normalizedArranger,
        );

        return new SongBasicTestData(
            name: $name,
            normalizedName: $normalizedName,
            songType: $songType,
            genres: $genres,
            agencyIdentifier: $agencyIdentifier,
            groupIdentifiers: $groupIdentifiers,
            talentIdentifiers: $talentIdentifiers,
            releaseDate: $releaseDate,
            albumName: $albumName,
            coverImageIdentifier: $coverImageIdentifier,
            lyricist: $lyricist,
            normalizedLyricist: $normalizedLyricist,
            composer: $composer,
            normalizedComposer: $normalizedComposer,
            arranger: $arranger,
            normalizedArranger: $normalizedArranger,
            songBasic: $songBasic,
        );
    }
}

/**
 * テストデータを保持するクラス
 */
readonly class SongBasicTestData
{
    /**
     * テストデータなので、すべてpublicで定義
     *
     * @param array<SongGenre> $genres
     * @param array<WikiIdentifier> $groupIdentifiers
     * @param array<WikiIdentifier> $talentIdentifiers
     */
    public function __construct(
        // 基本情報
        public Name $name,
        public string $normalizedName,
        public ?SongType $songType,
        public array $genres,
        // 関連エンティティ
        public ?WikiIdentifier $agencyIdentifier,
        public array $groupIdentifiers,
        public array $talentIdentifiers,
        // リリース情報
        public ?ReleaseDate $releaseDate,
        public ?string $albumName,
        public ?ImageIdentifier $coverImageIdentifier,
        // クレジット情報
        public Lyricist $lyricist,
        public string $normalizedLyricist,
        public Composer $composer,
        public string $normalizedComposer,
        public Arranger $arranger,
        public string $normalizedArranger,
        // SongBasic
        public SongBasic $songBasic,
    ) {
    }
}
