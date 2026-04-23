<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Infrastructure\Repository;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Wiki\Domain\Entity\WikiSnapshot;
use Source\Wiki\Wiki\Domain\Repository\WikiSnapshotRepositoryInterface;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Agency\AgencyBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Agency\CEO;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Group\GroupBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\BasicInterface;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\Emoji;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\FandomName;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\Name;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\RepresentativeSymbol;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Song\Arranger;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Song\Composer;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Song\Lyricist;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Song\SongBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Talent\Position;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Talent\RealName;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Talent\TalentBasic;
use Source\Wiki\Wiki\Domain\ValueObject\Section\SectionContentCollection;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\WikiSnapshotIdentifier;
use Tests\Helper\CreateWiki;
use Tests\Helper\CreateWikiSnapshot;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class WikiSnapshotRepositoryTest extends TestCase
{
    /**
     * 正常系：GroupタイプのWikiSnapshotが正しく保存されること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveWithGroupBasic(): void
    {
        $snapshotId = StrTestHelper::generateUuid();
        $wikiId = StrTestHelper::generateUuid();
        $editorId = StrTestHelper::generateUuid();
        $translationSetId = StrTestHelper::generateUuid();

        CreateWiki::create($wikiId, 'group', ['slug' => 'gr-twice-wiki']);

        $snapshot = new WikiSnapshot(
            new WikiSnapshotIdentifier($snapshotId),
            new WikiIdentifier($wikiId),
            new TranslationSetIdentifier($translationSetId),
            new Slug('gr-twice'),
            Language::KOREAN,
            ResourceType::GROUP,
            new GroupBasic(
                name: new Name('TWICE'),
                normalizedName: 'twice',
                agencyIdentifier: null,
                groupType: null,
                status: null,
                generation: null,
                debutDate: null,
                disbandDate: null,
                fandomName: new FandomName('ONCE'),
                officialColors: [],
                emoji: new Emoji(''),
                representativeSymbol: new RepresentativeSymbol(''),
                mainImageIdentifier: null,
            ),
            new SectionContentCollection([], allowBlocks: true),
            null,
            new Version(1),
            new PrincipalIdentifier($editorId),
            null,
            null,
            null,
            null,
            null,
            null,
            new DateTimeImmutable(),
        );

        $repository = $this->app->make(WikiSnapshotRepositoryInterface::class);
        $repository->save($snapshot);

        $this->assertDatabaseHas('wiki_snapshots', [
            'id' => $snapshotId,
            'wiki_id' => $wikiId,
            'translation_set_identifier' => $translationSetId,
            'slug' => 'gr-twice',
            'language' => Language::KOREAN->value,
            'resource_type' => ResourceType::GROUP->value,
            'version' => 1,
            'editor_id' => $editorId,
            'approver_id' => null,
            'merger_id' => null,
            'source_editor_id' => null,
        ]);
        $this->assertDatabaseHas('wiki_snapshot_group_basics', [
            'snapshot_id' => $snapshotId,
            'name' => 'TWICE',
            'normalized_name' => 'twice',
            'fandom_name' => 'ONCE',
        ]);
    }

    /**
     * 正常系：TalentタイプのWikiSnapshotが正しく保存されること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveWithTalentBasic(): void
    {
        $snapshotId = StrTestHelper::generateUuid();
        $wikiId = StrTestHelper::generateUuid();
        $editorId = StrTestHelper::generateUuid();
        $translationSetId = StrTestHelper::generateUuid();

        CreateWiki::create($wikiId, 'talent', ['slug' => 'tl-chaeyoung-wiki']);

        $snapshot = new WikiSnapshot(
            new WikiSnapshotIdentifier($snapshotId),
            new WikiIdentifier($wikiId),
            new TranslationSetIdentifier($translationSetId),
            new Slug('tl-chaeyoung'),
            Language::KOREAN,
            ResourceType::TALENT,
            new TalentBasic(
                name: new Name('채영'),
                normalizedName: 'chaeyoung',
                realName: new RealName('손채영'),
                normalizedRealName: 'sonchaeyoung',
                birthday: null,
                agencyIdentifier: null,
                groupIdentifiers: [],
                emoji: new Emoji(''),
                representativeSymbol: new RepresentativeSymbol(''),
                position: new Position(''),
                mbti: null,
                zodiacSign: null,
                englishLevel: null,
                height: null,
                bloodType: null,
                fandomName: new FandomName(''),
                profileImageIdentifier: null,
            ),
            new SectionContentCollection([], allowBlocks: true),
            null,
            new Version(1),
            new PrincipalIdentifier($editorId),
            null,
            null,
            null,
            null,
            null,
            null,
            new DateTimeImmutable(),
        );

        $repository = $this->app->make(WikiSnapshotRepositoryInterface::class);
        $repository->save($snapshot);

        $this->assertDatabaseHas('wiki_snapshots', [
            'id' => $snapshotId,
            'resource_type' => ResourceType::TALENT->value,
            'editor_id' => $editorId,
        ]);
        $this->assertDatabaseHas('wiki_snapshot_talent_basics', [
            'snapshot_id' => $snapshotId,
            'name' => '채영',
            'normalized_name' => 'chaeyoung',
            'real_name' => '손채영',
            'normalized_real_name' => 'sonchaeyoung',
        ]);
    }

    /**
     * 正常系：AgencyタイプのWikiSnapshotが正しく保存されること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveWithAgencyBasic(): void
    {
        $snapshotId = StrTestHelper::generateUuid();
        $wikiId = StrTestHelper::generateUuid();
        $editorId = StrTestHelper::generateUuid();
        $translationSetId = StrTestHelper::generateUuid();

        CreateWiki::create($wikiId, 'agency', ['slug' => 'ag-jyp-wiki']);

        $snapshot = new WikiSnapshot(
            new WikiSnapshotIdentifier($snapshotId),
            new WikiIdentifier($wikiId),
            new TranslationSetIdentifier($translationSetId),
            new Slug('ag-jyp-entertainment'),
            Language::ENGLISH,
            ResourceType::AGENCY,
            new AgencyBasic(
                name: new Name('JYP Entertainment'),
                normalizedName: 'jyp entertainment',
                ceo: new CEO('J.Y. Park'),
                normalizedCeo: 'j.y. park',
                foundedIn: null,
                parentAgencyIdentifier: null,
                status: null,
                logoImageIdentifier: null,
                officialWebsite: null,
                socialLinks: [],
            ),
            new SectionContentCollection([], allowBlocks: true),
            null,
            new Version(1),
            new PrincipalIdentifier($editorId),
            null,
            null,
            null,
            null,
            null,
            null,
            new DateTimeImmutable(),
        );

        $repository = $this->app->make(WikiSnapshotRepositoryInterface::class);
        $repository->save($snapshot);

        $this->assertDatabaseHas('wiki_snapshots', [
            'id' => $snapshotId,
            'resource_type' => ResourceType::AGENCY->value,
            'editor_id' => $editorId,
        ]);
        $this->assertDatabaseHas('wiki_snapshot_agency_basics', [
            'snapshot_id' => $snapshotId,
            'name' => 'JYP Entertainment',
            'normalized_name' => 'jyp entertainment',
            'ceo' => 'J.Y. Park',
            'normalized_ceo' => 'j.y. park',
        ]);
    }

    /**
     * 正常系：SongタイプのWikiSnapshotが正しく保存されること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveWithSongBasic(): void
    {
        $snapshotId = StrTestHelper::generateUuid();
        $wikiId = StrTestHelper::generateUuid();
        $editorId = StrTestHelper::generateUuid();
        $translationSetId = StrTestHelper::generateUuid();

        CreateWiki::create($wikiId, 'song', ['slug' => 'sg-tt-wiki']);

        $snapshot = new WikiSnapshot(
            new WikiSnapshotIdentifier($snapshotId),
            new WikiIdentifier($wikiId),
            new TranslationSetIdentifier($translationSetId),
            new Slug('sg-tt-song'),
            Language::KOREAN,
            ResourceType::SONG,
            new SongBasic(
                name: new Name('TT'),
                normalizedName: 'tt',
                songType: null,
                genres: [],
                agencyIdentifier: null,
                groupIdentifiers: [],
                talentIdentifiers: [],
                releaseDate: null,
                albumName: null,
                coverImageIdentifier: null,
                lyricist: new Lyricist('Black Eyed Pilseung'),
                normalizedLyricist: 'black eyed pilseung',
                composer: new Composer('Black Eyed Pilseung'),
                normalizedComposer: 'black eyed pilseung',
                arranger: new Arranger('Rado'),
                normalizedArranger: 'rado',
            ),
            new SectionContentCollection([], allowBlocks: true),
            null,
            new Version(1),
            new PrincipalIdentifier($editorId),
            null,
            null,
            null,
            null,
            null,
            null,
            new DateTimeImmutable(),
        );

        $repository = $this->app->make(WikiSnapshotRepositoryInterface::class);
        $repository->save($snapshot);

        $this->assertDatabaseHas('wiki_snapshots', [
            'id' => $snapshotId,
            'resource_type' => ResourceType::SONG->value,
            'editor_id' => $editorId,
        ]);
        $this->assertDatabaseHas('wiki_snapshot_song_basics', [
            'snapshot_id' => $snapshotId,
            'name' => 'TT',
            'normalized_name' => 'tt',
            'lyricist' => 'Black Eyed Pilseung',
            'normalized_lyricist' => 'black eyed pilseung',
            'composer' => 'Black Eyed Pilseung',
            'normalized_composer' => 'black eyed pilseung',
            'arranger' => 'Rado',
            'normalized_arranger' => 'rado',
        ]);
    }

    /**
     * 正常系：WikiIdentifierでWikiSnapshotが取得できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByWikiIdentifier(): void
    {
        $wikiId = StrTestHelper::generateUuid();
        $snapshotId1 = StrTestHelper::generateUuid();
        $snapshotId2 = StrTestHelper::generateUuid();
        $otherSnapshotId = StrTestHelper::generateUuid();

        CreateWikiSnapshot::create($snapshotId1, 'group', [
            'wiki_id' => $wikiId,
            'slug' => 'gr-twice-v1',
            'version' => 1,
        ]);
        CreateWikiSnapshot::create($snapshotId2, 'group', [
            'wiki_id' => $wikiId,
            'slug' => 'gr-twice-v2',
            'version' => 2,
        ]);
        CreateWikiSnapshot::create($otherSnapshotId, 'group', [
            'slug' => 'gr-other-snapshot',
        ]);

        $repository = $this->app->make(WikiSnapshotRepositoryInterface::class);
        $snapshots = $repository->findByWikiIdentifier(new WikiIdentifier($wikiId));

        $this->assertCount(2, $snapshots);
        $snapshotIds = array_map(
            static fn (WikiSnapshot $s): string => (string) $s->snapshotIdentifier(),
            $snapshots,
        );
        $this->assertContains($snapshotId1, $snapshotIds);
        $this->assertContains($snapshotId2, $snapshotIds);
        $this->assertNotContains($otherSnapshotId, $snapshotIds);
    }

    /**
     * 正常系：存在しないWikiIdentifierの場合、空配列が返却されること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByWikiIdentifierWhenNotExist(): void
    {
        $repository = $this->app->make(WikiSnapshotRepositoryInterface::class);
        $snapshots = $repository->findByWikiIdentifier(new WikiIdentifier(StrTestHelper::generateUuid()));

        $this->assertIsArray($snapshots);
        $this->assertEmpty($snapshots);
    }

    /**
     * 正常系：GroupタイプのWikiSnapshotがWikiIdentifierとVersionで取得できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByWikiAndVersionWithGroupBasic(): void
    {
        $wikiId = StrTestHelper::generateUuid();
        $snapshotIdV1 = StrTestHelper::generateUuid();
        $snapshotIdV2 = StrTestHelper::generateUuid();

        CreateWikiSnapshot::create($snapshotIdV1, 'group', [
            'wiki_id' => $wikiId,
            'slug' => 'gr-twice-v1',
            'version' => 1,
        ]);
        CreateWikiSnapshot::create($snapshotIdV2, 'group', [
            'wiki_id' => $wikiId,
            'slug' => 'gr-twice-v2',
            'version' => 2,
        ], [
            'name' => 'TWICE',
            'normalized_name' => 'twice',
            'fandom_name' => 'ONCE',
        ]);

        $repository = $this->app->make(WikiSnapshotRepositoryInterface::class);
        $found = $repository->findByWikiAndVersion(new WikiIdentifier($wikiId), new Version(2));

        $this->assertInstanceOf(WikiSnapshot::class, $found);
        $this->assertSame($snapshotIdV2, (string) $found->snapshotIdentifier());
        $this->assertSame(2, $found->version()->value());
        $this->assertSame(ResourceType::GROUP, $found->resourceType());
        $this->assertSame('TWICE', (string) $found->basic()->name());
        $this->assertSame('twice', $found->basic()->normalizedName());
    }

    /**
     * 正常系：TalentタイプのWikiSnapshotがWikiIdentifierとVersionで取得できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByWikiAndVersionWithTalentBasic(): void
    {
        $wikiId = StrTestHelper::generateUuid();
        $snapshotIdV1 = StrTestHelper::generateUuid();
        $snapshotIdV2 = StrTestHelper::generateUuid();

        CreateWikiSnapshot::create($snapshotIdV1, 'talent', [
            'wiki_id' => $wikiId,
            'slug' => 'tl-chaeyoung-v1',
            'version' => 1,
        ]);
        CreateWikiSnapshot::create($snapshotIdV2, 'talent', [
            'wiki_id' => $wikiId,
            'slug' => 'tl-chaeyoung-v2',
            'version' => 2,
        ], [
            'name' => '채영',
            'normalized_name' => 'chaeyoung',
            'real_name' => '손채영',
            'normalized_real_name' => 'sonchaeyoung',
        ]);

        $repository = $this->app->make(WikiSnapshotRepositoryInterface::class);
        $found = $repository->findByWikiAndVersion(new WikiIdentifier($wikiId), new Version(2));

        $this->assertInstanceOf(WikiSnapshot::class, $found);
        $this->assertSame($snapshotIdV2, (string) $found->snapshotIdentifier());
        $this->assertSame(2, $found->version()->value());
        $this->assertSame(ResourceType::TALENT, $found->resourceType());
        $this->assertSame('채영', (string) $found->basic()->name());
        $this->assertSame('chaeyoung', $found->basic()->normalizedName());
    }

    /**
     * 正常系：AgencyタイプのWikiSnapshotがWikiIdentifierとVersionで取得できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByWikiAndVersionWithAgencyBasic(): void
    {
        $wikiId = StrTestHelper::generateUuid();
        $snapshotIdV1 = StrTestHelper::generateUuid();
        $snapshotIdV2 = StrTestHelper::generateUuid();

        CreateWikiSnapshot::create($snapshotIdV1, 'agency', [
            'wiki_id' => $wikiId,
            'slug' => 'ag-jyp-v1',
            'version' => 1,
        ]);
        CreateWikiSnapshot::create($snapshotIdV2, 'agency', [
            'wiki_id' => $wikiId,
            'slug' => 'ag-jyp-v2',
            'version' => 2,
        ], [
            'name' => 'JYP Entertainment',
            'normalized_name' => 'jyp entertainment',
            'ceo' => 'J.Y. Park',
            'normalized_ceo' => 'j.y. park',
        ]);

        $repository = $this->app->make(WikiSnapshotRepositoryInterface::class);
        $found = $repository->findByWikiAndVersion(new WikiIdentifier($wikiId), new Version(2));

        $this->assertInstanceOf(WikiSnapshot::class, $found);
        $this->assertSame($snapshotIdV2, (string) $found->snapshotIdentifier());
        $this->assertSame(2, $found->version()->value());
        $this->assertSame(ResourceType::AGENCY, $found->resourceType());
        $this->assertSame('JYP Entertainment', (string) $found->basic()->name());
        $this->assertSame('jyp entertainment', $found->basic()->normalizedName());
    }

    /**
     * 正常系：SongタイプのWikiSnapshotがWikiIdentifierとVersionで取得できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByWikiAndVersionWithSongBasic(): void
    {
        $wikiId = StrTestHelper::generateUuid();
        $snapshotIdV1 = StrTestHelper::generateUuid();
        $snapshotIdV2 = StrTestHelper::generateUuid();

        CreateWikiSnapshot::create($snapshotIdV1, 'song', [
            'wiki_id' => $wikiId,
            'slug' => 'sg-tt-v1',
            'version' => 1,
        ]);
        CreateWikiSnapshot::create($snapshotIdV2, 'song', [
            'wiki_id' => $wikiId,
            'slug' => 'sg-tt-v2',
            'version' => 2,
        ], [
            'name' => 'TT',
            'normalized_name' => 'tt',
            'lyricist' => 'Black Eyed Pilseung',
            'normalized_lyricist' => 'black eyed pilseung',
            'composer' => 'Black Eyed Pilseung',
            'normalized_composer' => 'black eyed pilseung',
            'arranger' => 'Rado',
            'normalized_arranger' => 'rado',
        ]);

        $repository = $this->app->make(WikiSnapshotRepositoryInterface::class);
        $found = $repository->findByWikiAndVersion(new WikiIdentifier($wikiId), new Version(2));

        $this->assertInstanceOf(WikiSnapshot::class, $found);
        $this->assertSame($snapshotIdV2, (string) $found->snapshotIdentifier());
        $this->assertSame(2, $found->version()->value());
        $this->assertSame(ResourceType::SONG, $found->resourceType());
        $this->assertSame('TT', (string) $found->basic()->name());
        $this->assertSame('tt', $found->basic()->normalizedName());
    }

    /**
     * 正常系：存在しないWikiIdentifierとVersionの場合、nullが返却されること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByWikiAndVersionWhenNotExist(): void
    {
        $repository = $this->app->make(WikiSnapshotRepositoryInterface::class);
        $found = $repository->findByWikiAndVersion(
            new WikiIdentifier(StrTestHelper::generateUuid()),
            new Version(1),
        );

        $this->assertNull($found);
    }

    /**
     * 正常系：TranslationSetIdentifierとVersionでWikiSnapshotが取得できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByTranslationSetIdentifierAndVersion(): void
    {
        $translationSetId = StrTestHelper::generateUuid();
        $snapshotId1 = StrTestHelper::generateUuid();
        $snapshotId2 = StrTestHelper::generateUuid();
        $otherSnapshotId = StrTestHelper::generateUuid();

        CreateWikiSnapshot::create($snapshotId1, 'group', [
            'translation_set_identifier' => $translationSetId,
            'slug' => 'gr-twice-ko',
            'language' => Language::KOREAN->value,
            'version' => 2,
        ]);
        CreateWikiSnapshot::create($snapshotId2, 'group', [
            'translation_set_identifier' => $translationSetId,
            'slug' => 'gr-twice-en',
            'language' => Language::ENGLISH->value,
            'version' => 2,
        ]);
        CreateWikiSnapshot::create($otherSnapshotId, 'group', [
            'translation_set_identifier' => $translationSetId,
            'slug' => 'gr-twice-v1',
            'version' => 1,
        ]);

        $repository = $this->app->make(WikiSnapshotRepositoryInterface::class);
        $snapshots = $repository->findByTranslationSetIdentifierAndVersion(
            new TranslationSetIdentifier($translationSetId),
            new Version(2),
        );

        $this->assertCount(2, $snapshots);
        $snapshotIds = array_map(
            static fn (WikiSnapshot $s): string => (string) $s->snapshotIdentifier(),
            $snapshots,
        );
        $this->assertContains($snapshotId1, $snapshotIds);
        $this->assertContains($snapshotId2, $snapshotIds);
        $this->assertNotContains($otherSnapshotId, $snapshotIds);
    }

    /**
     * 正常系：存在しないTranslationSetIdentifierとVersionの場合、空配列が返却されること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByTranslationSetIdentifierAndVersionWhenNotExist(): void
    {
        $repository = $this->app->make(WikiSnapshotRepositoryInterface::class);
        $snapshots = $repository->findByTranslationSetIdentifierAndVersion(
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            new Version(1),
        );

        $this->assertIsArray($snapshots);
        $this->assertEmpty($snapshots);
    }

    /**
     * 異常系：IMAGEタイプのWikiSnapshotをsaveしようとした場合、InvalidArgumentExceptionがスローされること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveWithImageTypeThrowsException(): void
    {
        $snapshotId = StrTestHelper::generateUuid();
        $wikiId = StrTestHelper::generateUuid();
        $translationSetId = StrTestHelper::generateUuid();

        CreateWiki::create($wikiId, 'group', ['slug' => 'gr-image-save-wiki']);

        $basic = $this->createStub(BasicInterface::class);
        $basic->method('toArray')->willReturn(['type' => 'image']);

        $snapshot = new WikiSnapshot(
            new WikiSnapshotIdentifier($snapshotId),
            new WikiIdentifier($wikiId),
            new TranslationSetIdentifier($translationSetId),
            new Slug('gr-image-snapshot'),
            Language::KOREAN,
            ResourceType::IMAGE,
            $basic,
            new SectionContentCollection([], allowBlocks: true),
            null,
            new Version(1),
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            new DateTimeImmutable(),
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('IMAGE resource type does not have a Basic.');

        $repository = $this->app->make(WikiSnapshotRepositoryInterface::class);
        $repository->save($snapshot);
    }

    /**
     * 異常系：IMAGEタイプのWikiSnapshotをfindByWikiAndVersionで取得しようとした場合、InvalidArgumentExceptionがスローされること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByWikiAndVersionWithImageTypeThrowsException(): void
    {
        $wikiId = StrTestHelper::generateUuid();
        $snapshotId = StrTestHelper::generateUuid();

        DB::table('wikis')->insert([
            'id' => $wikiId,
            'translation_set_identifier' => StrTestHelper::generateUuid(),
            'slug' => 'gr-image-find-wiki',
            'language' => 'ko',
            'resource_type' => 'image',
            'sections' => json_encode([]),
            'version' => 1,
            'editor_id' => StrTestHelper::generateUuid(),
        ]);
        DB::table('wiki_snapshots')->insert([
            'id' => $snapshotId,
            'wiki_id' => $wikiId,
            'translation_set_identifier' => StrTestHelper::generateUuid(),
            'slug' => 'gr-image-snapshot-find',
            'language' => 'ko',
            'resource_type' => 'image',
            'sections' => json_encode([]),
            'version' => 1,
            'created_at' => now(),
        ]);

        $this->expectException(InvalidArgumentException::class);

        $repository = $this->app->make(WikiSnapshotRepositoryInterface::class);
        $repository->findByWikiAndVersion(new WikiIdentifier($wikiId), new Version(1));
    }

    /**
     * 異常系：TalentBasicが存在しないTalentタイプのWikiSnapshotをfindByWikiAndVersionで取得した場合、InvalidArgumentExceptionがスローされること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindWithMissingTalentBasicThrowsException(): void
    {
        $wikiId = StrTestHelper::generateUuid();
        $snapshotId = StrTestHelper::generateUuid();

        CreateWiki::create($wikiId, 'talent', ['slug' => 'tl-missing-talent-snap-wiki']);

        DB::table('wiki_snapshots')->insert([
            'id' => $snapshotId,
            'wiki_id' => $wikiId,
            'translation_set_identifier' => StrTestHelper::generateUuid(),
            'slug' => 'tl-missing-talent-snap',
            'language' => 'ko',
            'resource_type' => 'talent',
            'sections' => json_encode([]),
            'version' => 1,
            'created_at' => now(),
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('TalentBasic not found for WikiSnapshot.');

        $repository = $this->app->make(WikiSnapshotRepositoryInterface::class);
        $repository->findByWikiAndVersion(new WikiIdentifier($wikiId), new Version(1));
    }

    /**
     * 異常系：GroupBasicが存在しないGroupタイプのWikiSnapshotをfindByWikiAndVersionで取得した場合、InvalidArgumentExceptionがスローされること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindWithMissingGroupBasicThrowsException(): void
    {
        $wikiId = StrTestHelper::generateUuid();
        $snapshotId = StrTestHelper::generateUuid();

        CreateWiki::create($wikiId, 'group', ['slug' => 'gr-missing-group-snap-wiki']);

        DB::table('wiki_snapshots')->insert([
            'id' => $snapshotId,
            'wiki_id' => $wikiId,
            'translation_set_identifier' => StrTestHelper::generateUuid(),
            'slug' => 'gr-missing-group-snap',
            'language' => 'ko',
            'resource_type' => 'group',
            'sections' => json_encode([]),
            'version' => 1,
            'created_at' => now(),
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('GroupBasic not found for WikiSnapshot.');

        $repository = $this->app->make(WikiSnapshotRepositoryInterface::class);
        $repository->findByWikiAndVersion(new WikiIdentifier($wikiId), new Version(1));
    }

    /**
     * 異常系：AgencyBasicが存在しないAgencyタイプのWikiSnapshotをfindByWikiAndVersionで取得した場合、InvalidArgumentExceptionがスローされること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindWithMissingAgencyBasicThrowsException(): void
    {
        $wikiId = StrTestHelper::generateUuid();
        $snapshotId = StrTestHelper::generateUuid();

        CreateWiki::create($wikiId, 'agency', ['slug' => 'ag-missing-agency-snap-wiki']);

        DB::table('wiki_snapshots')->insert([
            'id' => $snapshotId,
            'wiki_id' => $wikiId,
            'translation_set_identifier' => StrTestHelper::generateUuid(),
            'slug' => 'ag-missing-agency-snap',
            'language' => 'ko',
            'resource_type' => 'agency',
            'sections' => json_encode([]),
            'version' => 1,
            'created_at' => now(),
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('AgencyBasic not found for WikiSnapshot.');

        $repository = $this->app->make(WikiSnapshotRepositoryInterface::class);
        $repository->findByWikiAndVersion(new WikiIdentifier($wikiId), new Version(1));
    }

    /**
     * 異常系：SongBasicが存在しないSongタイプのWikiSnapshotをfindByWikiAndVersionで取得した場合、InvalidArgumentExceptionがスローされること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindWithMissingSongBasicThrowsException(): void
    {
        $wikiId = StrTestHelper::generateUuid();
        $snapshotId = StrTestHelper::generateUuid();

        CreateWiki::create($wikiId, 'song', ['slug' => 'sg-missing-song-snap-wiki']);

        DB::table('wiki_snapshots')->insert([
            'id' => $snapshotId,
            'wiki_id' => $wikiId,
            'translation_set_identifier' => StrTestHelper::generateUuid(),
            'slug' => 'sg-missing-song-snap',
            'language' => 'ko',
            'resource_type' => 'song',
            'sections' => json_encode([]),
            'version' => 1,
            'created_at' => now(),
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('SongBasic not found for WikiSnapshot.');

        $repository = $this->app->make(WikiSnapshotRepositoryInterface::class);
        $repository->findByWikiAndVersion(new WikiIdentifier($wikiId), new Version(1));
    }
}
