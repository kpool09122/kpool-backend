<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Infrastructure\Repository;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Wiki\Domain\Entity\Wiki;
use Source\Wiki\Wiki\Domain\Repository\WikiRepositoryInterface;
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
use Tests\Helper\CreateWiki;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class WikiRepositoryTest extends TestCase
{
    /**
     * 正常系：指定したIDのWiki情報が存在しない場合、nullが返却されること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdWhenNotExist(): void
    {
        $repository = $this->app->make(WikiRepositoryInterface::class);
        $wiki = $repository->findById(new WikiIdentifier(StrTestHelper::generateUuid()));

        $this->assertNull($wiki);
    }

    /**
     * 正常系：GroupタイプのWikiがfindByIdで取得できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdWithGroupBasic(): void
    {
        $wikiId = StrTestHelper::generateUuid();
        $translationSetId = StrTestHelper::generateUuid();
        $editorId = StrTestHelper::generateUuid();
        $approverId = StrTestHelper::generateUuid();
        $mergerId = StrTestHelper::generateUuid();

        CreateWiki::create($wikiId, 'group', [
            'translation_set_identifier' => $translationSetId,
            'slug' => 'gr-twice',
            'language' => Language::KOREAN->value,
            'editor_id' => $editorId,
            'approver_id' => $approverId,
            'merger_id' => $mergerId,
        ], [
            'name' => 'TWICE',
            'normalized_name' => 'twice',
            'fandom_name' => 'ONCE',
        ]);

        $repository = $this->app->make(WikiRepositoryInterface::class);
        $found = $repository->findById(new WikiIdentifier($wikiId));

        $this->assertInstanceOf(Wiki::class, $found);
        $this->assertSame($wikiId, (string) $found->wikiIdentifier());
        $this->assertSame($translationSetId, (string) $found->translationSetIdentifier());
        $this->assertSame('gr-twice', (string) $found->slug());
        $this->assertSame(Language::KOREAN, $found->language());
        $this->assertSame(ResourceType::GROUP, $found->resourceType());
        $this->assertSame('TWICE', (string) $found->basic()->name());
        $this->assertSame('twice', $found->basic()->normalizedName());
        $this->assertSame($editorId, (string) $found->editorIdentifier());
        $this->assertSame($approverId, (string) $found->approverIdentifier());
        $this->assertSame($mergerId, (string) $found->mergerIdentifier());
        $this->assertNull($found->sourceEditorIdentifier());
        $this->assertNull($found->mergedAt());
        $this->assertNull($found->translatedAt());
        $this->assertNull($found->approvedAt());
    }

    /**
     * 正常系：TalentタイプのWikiがfindByIdで取得できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdWithTalentBasic(): void
    {
        $wikiId = StrTestHelper::generateUuid();
        $editorId = StrTestHelper::generateUuid();

        CreateWiki::create($wikiId, 'talent', [
            'slug' => 'tl-chaeyoung',
            'language' => Language::KOREAN->value,
            'editor_id' => $editorId,
        ], [
            'name' => '채영',
            'normalized_name' => 'chaeyoung',
            'real_name' => '손채영',
            'normalized_real_name' => 'sonchaeyoung',
        ]);

        $repository = $this->app->make(WikiRepositoryInterface::class);
        $found = $repository->findById(new WikiIdentifier($wikiId));

        $this->assertInstanceOf(Wiki::class, $found);
        $this->assertSame($wikiId, (string) $found->wikiIdentifier());
        $this->assertSame(ResourceType::TALENT, $found->resourceType());
        $this->assertSame('채영', (string) $found->basic()->name());
        $this->assertSame('chaeyoung', $found->basic()->normalizedName());
    }

    /**
     * 正常系：AgencyタイプのWikiがfindByIdで取得できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdWithAgencyBasic(): void
    {
        $wikiId = StrTestHelper::generateUuid();
        $editorId = StrTestHelper::generateUuid();

        CreateWiki::create($wikiId, 'agency', [
            'slug' => 'ag-jyp-entertainment',
            'language' => Language::ENGLISH->value,
            'editor_id' => $editorId,
        ], [
            'name' => 'JYP Entertainment',
            'normalized_name' => 'jyp entertainment',
            'ceo' => 'J.Y. Park',
            'normalized_ceo' => 'j.y. park',
        ]);

        $repository = $this->app->make(WikiRepositoryInterface::class);
        $found = $repository->findById(new WikiIdentifier($wikiId));

        $this->assertInstanceOf(Wiki::class, $found);
        $this->assertSame($wikiId, (string) $found->wikiIdentifier());
        $this->assertSame(ResourceType::AGENCY, $found->resourceType());
        $this->assertSame('JYP Entertainment', (string) $found->basic()->name());
        $this->assertSame('jyp entertainment', $found->basic()->normalizedName());
    }

    /**
     * 正常系：SongタイプのWikiがfindByIdで取得できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdWithSongBasic(): void
    {
        $wikiId = StrTestHelper::generateUuid();
        $editorId = StrTestHelper::generateUuid();

        CreateWiki::create($wikiId, 'song', [
            'slug' => 'sg-tt-song',
            'language' => Language::KOREAN->value,
            'editor_id' => $editorId,
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

        $repository = $this->app->make(WikiRepositoryInterface::class);
        $found = $repository->findById(new WikiIdentifier($wikiId));

        $this->assertInstanceOf(Wiki::class, $found);
        $this->assertSame($wikiId, (string) $found->wikiIdentifier());
        $this->assertSame(ResourceType::SONG, $found->resourceType());
        $this->assertSame('TT', (string) $found->basic()->name());
        $this->assertSame('tt', $found->basic()->normalizedName());
    }

    /**
     * 正常系：GroupタイプのWikiが正しく保存されること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveWithGroupBasic(): void
    {
        $wikiId = StrTestHelper::generateUuid();
        $editorId = StrTestHelper::generateUuid();
        $translationSetId = StrTestHelper::generateUuid();

        $wiki = new Wiki(
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
            null,
            new PrincipalIdentifier($editorId),
        );

        $repository = $this->app->make(WikiRepositoryInterface::class);
        $repository->save($wiki);

        $this->assertDatabaseHas('wikis', [
            'id' => $wikiId,
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
        $this->assertDatabaseHas('wiki_group_basics', [
            'wiki_id' => $wikiId,
            'name' => 'TWICE',
            'normalized_name' => 'twice',
            'fandom_name' => 'ONCE',
        ]);
    }

    /**
     * 正常系：TalentタイプのWikiが正しく保存されること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveWithTalentBasic(): void
    {
        $wikiId = StrTestHelper::generateUuid();
        $editorId = StrTestHelper::generateUuid();
        $translationSetId = StrTestHelper::generateUuid();

        $wiki = new Wiki(
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
            null,
            new PrincipalIdentifier($editorId),
        );

        $repository = $this->app->make(WikiRepositoryInterface::class);
        $repository->save($wiki);

        $this->assertDatabaseHas('wikis', [
            'id' => $wikiId,
            'resource_type' => ResourceType::TALENT->value,
            'editor_id' => $editorId,
        ]);
        $this->assertDatabaseHas('wiki_talent_basics', [
            'wiki_id' => $wikiId,
            'name' => '채영',
            'normalized_name' => 'chaeyoung',
            'real_name' => '손채영',
            'normalized_real_name' => 'sonchaeyoung',
        ]);
    }

    /**
     * 正常系：AgencyタイプのWikiが正しく保存されること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveWithAgencyBasic(): void
    {
        $wikiId = StrTestHelper::generateUuid();
        $editorId = StrTestHelper::generateUuid();
        $translationSetId = StrTestHelper::generateUuid();

        $wiki = new Wiki(
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
            null,
            new PrincipalIdentifier($editorId),
        );

        $repository = $this->app->make(WikiRepositoryInterface::class);
        $repository->save($wiki);

        $this->assertDatabaseHas('wikis', [
            'id' => $wikiId,
            'resource_type' => ResourceType::AGENCY->value,
            'editor_id' => $editorId,
        ]);
        $this->assertDatabaseHas('wiki_agency_basics', [
            'wiki_id' => $wikiId,
            'name' => 'JYP Entertainment',
            'normalized_name' => 'jyp entertainment',
            'ceo' => 'J.Y. Park',
            'normalized_ceo' => 'j.y. park',
        ]);
    }

    /**
     * 正常系：SongタイプのWikiが正しく保存されること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveWithSongBasic(): void
    {
        $wikiId = StrTestHelper::generateUuid();
        $editorId = StrTestHelper::generateUuid();
        $translationSetId = StrTestHelper::generateUuid();

        $wiki = new Wiki(
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
            null,
            new PrincipalIdentifier($editorId),
        );

        $repository = $this->app->make(WikiRepositoryInterface::class);
        $repository->save($wiki);

        $this->assertDatabaseHas('wikis', [
            'id' => $wikiId,
            'resource_type' => ResourceType::SONG->value,
            'editor_id' => $editorId,
        ]);
        $this->assertDatabaseHas('wiki_song_basics', [
            'wiki_id' => $wikiId,
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
     * 異常系：ImageタイプのWikiをfindByIdで取得しようとした場合、InvalidArgumentExceptionがスローされること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdWithImageTypeThrowsException(): void
    {
        $wikiId = StrTestHelper::generateUuid();

        DB::table('wikis')->insert([
            'id' => $wikiId,
            'translation_set_identifier' => StrTestHelper::generateUuid(),
            'slug' => 'gr-test-image',
            'language' => 'ko',
            'resource_type' => 'image',
            'sections' => json_encode([]),
            'theme_color' => null,
            'version' => 1,
            'owner_account_id' => null,
            'editor_id' => StrTestHelper::generateUuid(),
            'approver_id' => null,
            'merger_id' => null,
            'source_editor_id' => null,
            'merged_at' => null,
            'translated_at' => null,
            'approved_at' => null,
        ]);

        $this->expectException(InvalidArgumentException::class);

        $repository = $this->app->make(WikiRepositoryInterface::class);
        $repository->findById(new WikiIdentifier($wikiId));
    }

    /**
     * 正常系：SlugとLanguageでWikiが取得できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindBySlugAndLanguage(): void
    {
        $wikiId = StrTestHelper::generateUuid();

        CreateWiki::create($wikiId, 'group', [
            'slug' => 'gr-twice-slug',
            'language' => Language::KOREAN->value,
        ]);

        $repository = $this->app->make(WikiRepositoryInterface::class);
        $found = $repository->findBySlugAndLanguage(new Slug('gr-twice-slug'), Language::KOREAN);

        $this->assertInstanceOf(Wiki::class, $found);
        $this->assertSame($wikiId, (string) $found->wikiIdentifier());
    }

    /**
     * 正常系：存在しないSlugとLanguageの場合、nullが返却されること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindBySlugAndLanguageWhenNotExist(): void
    {
        $repository = $this->app->make(WikiRepositoryInterface::class);
        $found = $repository->findBySlugAndLanguage(new Slug('gr-not-exist'), Language::KOREAN);

        $this->assertNull($found);
    }

    /**
     * 正常系：existsBySlugでSlugが存在する場合、trueが返却されること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testExistsBySlugWhenExist(): void
    {
        $wikiId = StrTestHelper::generateUuid();

        CreateWiki::create($wikiId, 'group', [
            'slug' => 'gr-exists-slug',
        ]);

        $repository = $this->app->make(WikiRepositoryInterface::class);
        $exists = $repository->existsBySlug(new Slug('gr-exists-slug'));

        $this->assertTrue($exists);
    }

    /**
     * 正常系：existsBySlugでSlugが存在しない場合、falseが返却されること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testExistsBySlugWhenNotExist(): void
    {
        $repository = $this->app->make(WikiRepositoryInterface::class);
        $exists = $repository->existsBySlug(new Slug('gr-not-exist-slug'));

        $this->assertFalse($exists);
    }

    /**
     * 正常系：TranslationSetIdentifierでWikiが取得できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByTranslationSetIdentifier(): void
    {
        $translationSetId = StrTestHelper::generateUuid();
        $wikiId1 = StrTestHelper::generateUuid();
        $wikiId2 = StrTestHelper::generateUuid();
        $otherWikiId = StrTestHelper::generateUuid();

        CreateWiki::create($wikiId1, 'group', [
            'translation_set_identifier' => $translationSetId,
            'slug' => 'gr-twice-ko',
            'language' => Language::KOREAN->value,
        ]);
        CreateWiki::create($wikiId2, 'group', [
            'translation_set_identifier' => $translationSetId,
            'slug' => 'gr-twice-en',
            'language' => Language::ENGLISH->value,
        ]);
        CreateWiki::create($otherWikiId, 'group', [
            'slug' => 'gr-other-group',
        ]);

        $repository = $this->app->make(WikiRepositoryInterface::class);
        $wikis = $repository->findByTranslationSetIdentifier(new TranslationSetIdentifier($translationSetId));

        $this->assertCount(2, $wikis);
        $wikiIds = array_map(static fn (Wiki $wiki): string => (string) $wiki->wikiIdentifier(), $wikis);
        $this->assertContains($wikiId1, $wikiIds);
        $this->assertContains($wikiId2, $wikiIds);
        $this->assertNotContains($otherWikiId, $wikiIds);
    }

    /**
     * 正常系：存在しないTranslationSetIdentifierの場合、空配列が返却されること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByTranslationSetIdentifierWhenNotExist(): void
    {
        $repository = $this->app->make(WikiRepositoryInterface::class);
        $wikis = $repository->findByTranslationSetIdentifier(
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
        );

        $this->assertIsArray($wikis);
        $this->assertEmpty($wikis);
    }

    /**
     * 正常系：ResourceTypeでWikiが取得できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByResourceType(): void
    {
        $wikiId1 = StrTestHelper::generateUuid();
        $wikiId2 = StrTestHelper::generateUuid();
        $otherWikiId = StrTestHelper::generateUuid();

        CreateWiki::create($wikiId1, 'group', [
            'slug' => 'gr-group-wiki-1',
        ]);
        CreateWiki::create($wikiId2, 'group', [
            'slug' => 'gr-group-wiki-2',
        ]);
        CreateWiki::create($otherWikiId, 'talent', [
            'slug' => 'tl-talent-wiki',
        ]);

        $repository = $this->app->make(WikiRepositoryInterface::class);
        $wikis = $repository->findByResourceType(ResourceType::GROUP);

        $this->assertCount(2, $wikis);
        $wikiIds = array_map(static fn (Wiki $wiki): string => (string) $wiki->wikiIdentifier(), $wikis);
        $this->assertContains($wikiId1, $wikiIds);
        $this->assertContains($wikiId2, $wikiIds);
        $this->assertNotContains($otherWikiId, $wikiIds);
    }

    /**
     * 正常系：findByResourceTypeでlimitとoffsetが正しく動作すること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByResourceTypeWithLimitAndOffset(): void
    {
        $wikiId1 = StrTestHelper::generateUuid();
        $wikiId2 = StrTestHelper::generateUuid();
        $wikiId3 = StrTestHelper::generateUuid();

        CreateWiki::create($wikiId1, 'group', [
            'slug' => 'gr-group-limit-1',
        ]);
        CreateWiki::create($wikiId2, 'group', [
            'slug' => 'gr-group-limit-2',
        ]);
        CreateWiki::create($wikiId3, 'group', [
            'slug' => 'gr-group-limit-3',
        ]);

        $repository = $this->app->make(WikiRepositoryInterface::class);
        $wikis = $repository->findByResourceType(ResourceType::GROUP, limit: 2, offset: 0);

        $this->assertCount(2, $wikis);
    }

    /**
     * 正常系：OwnerAccountIdとResourceTypeでWikiが取得できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByOwnerAccountId(): void
    {
        $wikiId = StrTestHelper::generateUuid();
        $ownerAccountId = StrTestHelper::generateUuid();

        CreateWiki::create($wikiId, 'talent', [
            'slug' => 'tl-owner-talent',
            'owner_account_id' => $ownerAccountId,
        ]);

        $repository = $this->app->make(WikiRepositoryInterface::class);
        $found = $repository->findByOwnerAccountId(
            new AccountIdentifier($ownerAccountId),
            ResourceType::TALENT,
        );

        $this->assertInstanceOf(Wiki::class, $found);
        $this->assertSame($wikiId, (string) $found->wikiIdentifier());
        $this->assertSame(ResourceType::TALENT, $found->resourceType());
        $this->assertSame($ownerAccountId, (string) $found->ownerAccountIdentifier());
    }

    /**
     * 正常系：存在しないOwnerAccountIdの場合、nullが返却されること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByOwnerAccountIdWhenNotExist(): void
    {
        $repository = $this->app->make(WikiRepositoryInterface::class);
        $found = $repository->findByOwnerAccountId(
            new AccountIdentifier(StrTestHelper::generateUuid()),
            ResourceType::TALENT,
        );

        $this->assertNull($found);
    }

    /**
     * 正常系：OwnerAccountIdが一致してもResourceTypeが異なる場合、nullが返却されること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByOwnerAccountIdWithDifferentResourceType(): void
    {
        $wikiId = StrTestHelper::generateUuid();
        $ownerAccountId = StrTestHelper::generateUuid();

        CreateWiki::create($wikiId, 'group', [
            'slug' => 'gr-owner-group',
            'owner_account_id' => $ownerAccountId,
        ]);

        $repository = $this->app->make(WikiRepositoryInterface::class);
        $found = $repository->findByOwnerAccountId(
            new AccountIdentifier($ownerAccountId),
            ResourceType::TALENT,
        );

        $this->assertNull($found);
    }

    /**
     * 正常系：Wikiが正しく削除されること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testDelete(): void
    {
        $wikiId = StrTestHelper::generateUuid();

        CreateWiki::create($wikiId, 'group', [
            'slug' => 'gr-delete-target',
        ]);

        $repository = $this->app->make(WikiRepositoryInterface::class);
        $found = $repository->findById(new WikiIdentifier($wikiId));

        $this->assertInstanceOf(Wiki::class, $found);

        $repository->delete($found);

        $this->assertDatabaseMissing('wikis', ['id' => $wikiId]);
    }

    /**
     * 異常系：IMAGEタイプのWikiをsaveしようとした場合、InvalidArgumentExceptionがスローされること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveWithImageTypeThrowsException(): void
    {
        $wikiId = StrTestHelper::generateUuid();
        $editorId = StrTestHelper::generateUuid();
        $translationSetId = StrTestHelper::generateUuid();

        $basic = $this->createStub(BasicInterface::class);
        $basic->method('toArray')->willReturn(['type' => 'image']);

        $wiki = new Wiki(
            new WikiIdentifier($wikiId),
            new TranslationSetIdentifier($translationSetId),
            new Slug('gr-test-image-save'),
            Language::KOREAN,
            ResourceType::IMAGE,
            $basic,
            new SectionContentCollection([], allowBlocks: true),
            null,
            new Version(1),
            null,
            new PrincipalIdentifier($editorId),
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('IMAGE resource type does not have a Basic.');

        $repository = $this->app->make(WikiRepositoryInterface::class);
        $repository->save($wiki);
    }

    /**
     * 異常系：TalentBasicが存在しないTalentタイプのWikiをfindByIdで取得した場合、InvalidArgumentExceptionがスローされること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdWithMissingTalentBasicThrowsException(): void
    {
        $wikiId = StrTestHelper::generateUuid();

        DB::table('wikis')->insert([
            'id' => $wikiId,
            'translation_set_identifier' => StrTestHelper::generateUuid(),
            'slug' => 'tl-missing-talent-basic',
            'language' => 'ko',
            'resource_type' => 'talent',
            'sections' => json_encode([]),
            'version' => 1,
            'editor_id' => StrTestHelper::generateUuid(),
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('TalentBasic not found for Wiki.');

        $repository = $this->app->make(WikiRepositoryInterface::class);
        $repository->findById(new WikiIdentifier($wikiId));
    }

    /**
     * 異常系：GroupBasicが存在しないGroupタイプのWikiをfindByIdで取得した場合、InvalidArgumentExceptionがスローされること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdWithMissingGroupBasicThrowsException(): void
    {
        $wikiId = StrTestHelper::generateUuid();

        DB::table('wikis')->insert([
            'id' => $wikiId,
            'translation_set_identifier' => StrTestHelper::generateUuid(),
            'slug' => 'gr-missing-group-basic',
            'language' => 'ko',
            'resource_type' => 'group',
            'sections' => json_encode([]),
            'version' => 1,
            'editor_id' => StrTestHelper::generateUuid(),
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('GroupBasic not found for Wiki.');

        $repository = $this->app->make(WikiRepositoryInterface::class);
        $repository->findById(new WikiIdentifier($wikiId));
    }

    /**
     * 異常系：AgencyBasicが存在しないAgencyタイプのWikiをfindByIdで取得した場合、InvalidArgumentExceptionがスローされること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdWithMissingAgencyBasicThrowsException(): void
    {
        $wikiId = StrTestHelper::generateUuid();

        DB::table('wikis')->insert([
            'id' => $wikiId,
            'translation_set_identifier' => StrTestHelper::generateUuid(),
            'slug' => 'ag-missing-agency-basic',
            'language' => 'ko',
            'resource_type' => 'agency',
            'sections' => json_encode([]),
            'version' => 1,
            'editor_id' => StrTestHelper::generateUuid(),
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('AgencyBasic not found for Wiki.');

        $repository = $this->app->make(WikiRepositoryInterface::class);
        $repository->findById(new WikiIdentifier($wikiId));
    }

    /**
     * 異常系：SongBasicが存在しないSongタイプのWikiをfindByIdで取得した場合、InvalidArgumentExceptionがスローされること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdWithMissingSongBasicThrowsException(): void
    {
        $wikiId = StrTestHelper::generateUuid();

        DB::table('wikis')->insert([
            'id' => $wikiId,
            'translation_set_identifier' => StrTestHelper::generateUuid(),
            'slug' => 'sg-missing-song-basic',
            'language' => 'ko',
            'resource_type' => 'song',
            'sections' => json_encode([]),
            'version' => 1,
            'editor_id' => StrTestHelper::generateUuid(),
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('SongBasic not found for Wiki.');

        $repository = $this->app->make(WikiRepositoryInterface::class);
        $repository->findById(new WikiIdentifier($wikiId));
    }
}
