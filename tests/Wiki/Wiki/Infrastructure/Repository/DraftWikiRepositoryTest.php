<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Infrastructure\Repository;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Wiki\Domain\Entity\DraftWiki;
use Source\Wiki\Wiki\Domain\Repository\DraftWikiRepositoryInterface;
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
use Source\Wiki\Wiki\Domain\ValueObject\DraftWikiIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\Section\SectionContentCollection;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Tests\Helper\CreateDraftWiki;
use Tests\Helper\CreateWiki;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class DraftWikiRepositoryTest extends TestCase
{
    /**
     * 正常系：指定したIDの下書きWiki情報が存在しない場合、nullが返却されること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdWhenNotExist(): void
    {
        $repository = $this->app->make(DraftWikiRepositoryInterface::class);
        $wiki = $repository->findById(new DraftWikiIdentifier(StrTestHelper::generateUuid()));

        $this->assertNull($wiki);
    }

    /**
     * 正常系：GroupタイプのDraftWikiがfindByIdで取得できること.
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

        CreateDraftWiki::create($wikiId, 'group', [
            'translation_set_identifier' => $translationSetId,
            'slug' => 'gr-twice',
            'language' => Language::KOREAN->value,
            'status' => ApprovalStatus::Pending->value,
            'editor_id' => $editorId,
            'approver_id' => $approverId,
            'merger_id' => $mergerId,
        ], [
            'name' => 'TWICE',
            'normalized_name' => 'twice',
            'fandom_name' => 'ONCE',
        ]);

        $repository = $this->app->make(DraftWikiRepositoryInterface::class);
        $found = $repository->findById(new DraftWikiIdentifier($wikiId));

        $this->assertInstanceOf(DraftWiki::class, $found);
        $this->assertSame($wikiId, (string)$found->wikiIdentifier());
        $this->assertNull($found->publishedWikiIdentifier());
        $this->assertSame($translationSetId, (string)$found->translationSetIdentifier());
        $this->assertSame('gr-twice', (string)$found->slug());
        $this->assertSame(Language::KOREAN, $found->language());
        $this->assertSame(ResourceType::GROUP, $found->resourceType());
        $this->assertSame('TWICE', (string)$found->basic()->name());
        $this->assertSame('twice', $found->basic()->normalizedName());
        $this->assertSame(ApprovalStatus::Pending, $found->status());
        $this->assertSame($editorId, (string)$found->editorIdentifier());
        $this->assertSame($approverId, (string)$found->approverIdentifier());
        $this->assertSame($mergerId, (string)$found->mergerIdentifier());
        $this->assertNull($found->sourceEditorIdentifier());
        $this->assertNull($found->mergedAt());
        $this->assertNull($found->translatedAt());
        $this->assertNull($found->approvedAt());
    }

    /**
     * 正常系：TalentタイプのDraftWikiがfindByIdで取得できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdWithTalentBasic(): void
    {
        $wikiId = StrTestHelper::generateUuid();
        $editorId = StrTestHelper::generateUuid();

        CreateDraftWiki::create($wikiId, 'talent', [
            'slug' => 'tl-chaeyoung',
            'language' => Language::KOREAN->value,
            'editor_id' => $editorId,
        ], [
            'name' => '채영',
            'normalized_name' => 'chaeyoung',
            'real_name' => '손채영',
            'normalized_real_name' => 'sonchaeyoung',
        ]);

        $repository = $this->app->make(DraftWikiRepositoryInterface::class);
        $found = $repository->findById(new DraftWikiIdentifier($wikiId));

        $this->assertInstanceOf(DraftWiki::class, $found);
        $this->assertSame($wikiId, (string)$found->wikiIdentifier());
        $this->assertSame(ResourceType::TALENT, $found->resourceType());
        $this->assertSame('채영', (string)$found->basic()->name());
        $this->assertSame('chaeyoung', $found->basic()->normalizedName());
    }

    /**
     * 正常系：AgencyタイプのDraftWikiがfindByIdで取得できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdWithAgencyBasic(): void
    {
        $wikiId = StrTestHelper::generateUuid();
        $editorId = StrTestHelper::generateUuid();

        CreateDraftWiki::create($wikiId, 'agency', [
            'slug' => 'ag-jyp-entertainment',
            'language' => Language::ENGLISH->value,
            'editor_id' => $editorId,
        ], [
            'name' => 'JYP Entertainment',
            'normalized_name' => 'jyp entertainment',
            'ceo' => 'J.Y. Park',
            'normalized_ceo' => 'j.y. park',
        ]);

        $repository = $this->app->make(DraftWikiRepositoryInterface::class);
        $found = $repository->findById(new DraftWikiIdentifier($wikiId));

        $this->assertInstanceOf(DraftWiki::class, $found);
        $this->assertSame($wikiId, (string)$found->wikiIdentifier());
        $this->assertSame(ResourceType::AGENCY, $found->resourceType());
        $this->assertSame('JYP Entertainment', (string)$found->basic()->name());
        $this->assertSame('jyp entertainment', $found->basic()->normalizedName());
    }

    /**
     * 正常系：SongタイプのDraftWikiがfindByIdで取得できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdWithSongBasic(): void
    {
        $wikiId = StrTestHelper::generateUuid();
        $editorId = StrTestHelper::generateUuid();

        CreateDraftWiki::create($wikiId, 'song', [
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

        $repository = $this->app->make(DraftWikiRepositoryInterface::class);
        $found = $repository->findById(new DraftWikiIdentifier($wikiId));

        $this->assertInstanceOf(DraftWiki::class, $found);
        $this->assertSame($wikiId, (string)$found->wikiIdentifier());
        $this->assertSame(ResourceType::SONG, $found->resourceType());
        $this->assertSame('TT', (string)$found->basic()->name());
        $this->assertSame('tt', $found->basic()->normalizedName());
    }

    /**
     * 正常系：GroupタイプのDraftWikiが正しく保存されること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveWithGroupBasic(): void
    {
        $wikiId = StrTestHelper::generateUuid();
        $editorId = StrTestHelper::generateUuid();
        $translationSetId = StrTestHelper::generateUuid();

        $draftWiki = new DraftWiki(
            new DraftWikiIdentifier($wikiId),
            null,
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
            new SectionContentCollection([], allowBlocks: false),
            null,
            ApprovalStatus::Pending,
            new PrincipalIdentifier($editorId),
        );

        $repository = $this->app->make(DraftWikiRepositoryInterface::class);
        $repository->save($draftWiki);

        $this->assertDatabaseHas('draft_wikis', [
            'id' => $wikiId,
            'published_wiki_id' => null,
            'translation_set_identifier' => $translationSetId,
            'slug' => 'gr-twice',
            'language' => Language::KOREAN->value,
            'resource_type' => ResourceType::GROUP->value,
            'status' => ApprovalStatus::Pending->value,
            'editor_id' => $editorId,
            'approver_id' => null,
            'merger_id' => null,
            'source_editor_id' => null,
        ]);
        $this->assertDatabaseHas('draft_wiki_group_basics', [
            'wiki_id' => $wikiId,
            'name' => 'TWICE',
            'normalized_name' => 'twice',
            'fandom_name' => 'ONCE',
        ]);
    }

    /**
     * 正常系：TalentタイプのDraftWikiが正しく保存されること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveWithTalentBasic(): void
    {
        $wikiId = StrTestHelper::generateUuid();
        $editorId = StrTestHelper::generateUuid();
        $translationSetId = StrTestHelper::generateUuid();

        $draftWiki = new DraftWiki(
            new DraftWikiIdentifier($wikiId),
            null,
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
            new SectionContentCollection([], allowBlocks: false),
            null,
            ApprovalStatus::Pending,
            new PrincipalIdentifier($editorId),
        );

        $repository = $this->app->make(DraftWikiRepositoryInterface::class);
        $repository->save($draftWiki);

        $this->assertDatabaseHas('draft_wikis', [
            'id' => $wikiId,
            'resource_type' => ResourceType::TALENT->value,
            'editor_id' => $editorId,
        ]);
        $this->assertDatabaseHas('draft_wiki_talent_basics', [
            'wiki_id' => $wikiId,
            'name' => '채영',
            'normalized_name' => 'chaeyoung',
            'real_name' => '손채영',
            'normalized_real_name' => 'sonchaeyoung',
        ]);
    }

    /**
     * 正常系：AgencyタイプのDraftWikiが正しく保存されること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveWithAgencyBasic(): void
    {
        $wikiId = StrTestHelper::generateUuid();
        $editorId = StrTestHelper::generateUuid();
        $translationSetId = StrTestHelper::generateUuid();

        $draftWiki = new DraftWiki(
            new DraftWikiIdentifier($wikiId),
            null,
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
            new SectionContentCollection([], allowBlocks: false),
            null,
            ApprovalStatus::Pending,
            new PrincipalIdentifier($editorId),
        );

        $repository = $this->app->make(DraftWikiRepositoryInterface::class);
        $repository->save($draftWiki);

        $this->assertDatabaseHas('draft_wikis', [
            'id' => $wikiId,
            'resource_type' => ResourceType::AGENCY->value,
            'editor_id' => $editorId,
        ]);
        $this->assertDatabaseHas('draft_wiki_agency_basics', [
            'wiki_id' => $wikiId,
            'name' => 'JYP Entertainment',
            'normalized_name' => 'jyp entertainment',
            'ceo' => 'J.Y. Park',
            'normalized_ceo' => 'j.y. park',
        ]);
    }

    /**
     * 正常系：SongタイプのDraftWikiが正しく保存されること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveWithSongBasic(): void
    {
        $wikiId = StrTestHelper::generateUuid();
        $editorId = StrTestHelper::generateUuid();
        $translationSetId = StrTestHelper::generateUuid();

        $draftWiki = new DraftWiki(
            new DraftWikiIdentifier($wikiId),
            null,
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
            new SectionContentCollection([], allowBlocks: false),
            null,
            ApprovalStatus::Pending,
            new PrincipalIdentifier($editorId),
        );

        $repository = $this->app->make(DraftWikiRepositoryInterface::class);
        $repository->save($draftWiki);

        $this->assertDatabaseHas('draft_wikis', [
            'id' => $wikiId,
            'resource_type' => ResourceType::SONG->value,
            'editor_id' => $editorId,
        ]);
        $this->assertDatabaseHas('draft_wiki_song_basics', [
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
     * 異常系：ImageタイプのDraftWikiをfindByIdで取得しようとした場合、InvalidArgumentExceptionがスローされること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdWithImageTypeThrowsException(): void
    {
        $wikiId = StrTestHelper::generateUuid();

        DB::table('draft_wikis')->insert([
            'id' => $wikiId,
            'published_wiki_id' => null,
            'translation_set_identifier' => StrTestHelper::generateUuid(),
            'slug' => 'gr-test-image',
            'language' => 'ko',
            'resource_type' => 'image',
            'sections' => json_encode([]),
            'theme_color' => null,
            'status' => 'pending',
            'editor_id' => StrTestHelper::generateUuid(),
            'approver_id' => null,
            'merger_id' => null,
            'source_editor_id' => null,
            'merged_at' => null,
            'translated_at' => null,
            'approved_at' => null,
        ]);

        $this->expectException(InvalidArgumentException::class);

        $repository = $this->app->make(DraftWikiRepositoryInterface::class);
        $repository->findById(new DraftWikiIdentifier($wikiId));
    }

    /**
     * 正常系：SlugとLanguageで下書きWikiが取得できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindBySlugAndLanguage(): void
    {
        $wikiId = StrTestHelper::generateUuid();

        CreateDraftWiki::create($wikiId, 'group', [
            'slug' => 'gr-twice-slug',
            'language' => Language::KOREAN->value,
        ]);

        $repository = $this->app->make(DraftWikiRepositoryInterface::class);
        $found = $repository->findBySlugAndLanguage(new Slug('gr-twice-slug'), Language::KOREAN);

        $this->assertInstanceOf(DraftWiki::class, $found);
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
        $repository = $this->app->make(DraftWikiRepositoryInterface::class);
        $found = $repository->findBySlugAndLanguage(new Slug('gr-not-exist'), Language::KOREAN);

        $this->assertNull($found);
    }

    /**
     * 正常系：PublishedWikiIdentifierで下書きWikiが取得できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByPublishedWikiIdentifier(): void
    {
        $wikiId = StrTestHelper::generateUuid();
        $publishedWikiId = StrTestHelper::generateUuid();

        CreateWiki::create($publishedWikiId, 'group');

        CreateDraftWiki::create($wikiId, 'group', [
            'published_wiki_id' => $publishedWikiId,
        ]);

        $repository = $this->app->make(DraftWikiRepositoryInterface::class);
        $found = $repository->findByPublishedWikiIdentifier(new WikiIdentifier($publishedWikiId));

        $this->assertInstanceOf(DraftWiki::class, $found);
        $this->assertSame($wikiId, (string) $found->wikiIdentifier());
        $this->assertSame($publishedWikiId, (string) $found->publishedWikiIdentifier());
    }

    /**
     * 正常系：存在しないPublishedWikiIdentifierの場合、nullが返却されること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByPublishedWikiIdentifierWhenNotExist(): void
    {
        $repository = $this->app->make(DraftWikiRepositoryInterface::class);
        $found = $repository->findByPublishedWikiIdentifier(new WikiIdentifier(StrTestHelper::generateUuid()));

        $this->assertNull($found);
    }

    /**
     * 正常系：TranslationSetIdentifierで下書きWikiが取得できること.
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

        CreateDraftWiki::create($wikiId1, 'group', [
            'translation_set_identifier' => $translationSetId,
            'slug' => 'gr-twice-ko',
            'language' => Language::KOREAN->value,
        ]);
        CreateDraftWiki::create($wikiId2, 'group', [
            'translation_set_identifier' => $translationSetId,
            'slug' => 'gr-twice-en',
            'language' => Language::ENGLISH->value,
        ]);
        CreateDraftWiki::create($otherWikiId, 'group', [
            'slug' => 'gr-other-group',
        ]);

        $repository = $this->app->make(DraftWikiRepositoryInterface::class);
        $drafts = $repository->findByTranslationSetIdentifier(new TranslationSetIdentifier($translationSetId));

        $this->assertCount(2, $drafts);
        $draftIds = array_map(static fn (DraftWiki $draft): string => (string) $draft->wikiIdentifier(), $drafts);
        $this->assertContains($wikiId1, $draftIds);
        $this->assertContains($wikiId2, $draftIds);
        $this->assertNotContains($otherWikiId, $draftIds);
    }

    /**
     * 正常系：存在しないTranslationSetIdentifierの場合、空配列が返却されること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByTranslationSetIdentifierWhenNotExist(): void
    {
        $repository = $this->app->make(DraftWikiRepositoryInterface::class);
        $drafts = $repository->findByTranslationSetIdentifier(
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
        );

        $this->assertIsArray($drafts);
        $this->assertEmpty($drafts);
    }

    /**
     * 正常系：EditorIdentifierで下書きWikiが取得できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByEditorIdentifier(): void
    {
        $editorId = StrTestHelper::generateUuid();
        $wikiId1 = StrTestHelper::generateUuid();
        $wikiId2 = StrTestHelper::generateUuid();
        $otherWikiId = StrTestHelper::generateUuid();

        CreateDraftWiki::create($wikiId1, 'group', [
            'slug' => 'gr-editor-wiki-1',
            'editor_id' => $editorId,
        ]);
        CreateDraftWiki::create($wikiId2, 'talent', [
            'slug' => 'tl-editor-wiki-2',
            'editor_id' => $editorId,
        ]);
        CreateDraftWiki::create($otherWikiId, 'group', [
            'slug' => 'gr-other-editor-wiki',
        ]);

        $repository = $this->app->make(DraftWikiRepositoryInterface::class);
        $drafts = $repository->findByEditorIdentifier(new PrincipalIdentifier($editorId));

        $this->assertCount(2, $drafts);
        $draftIds = array_map(static fn (DraftWiki $draft): string => (string) $draft->wikiIdentifier(), $drafts);
        $this->assertContains($wikiId1, $draftIds);
        $this->assertContains($wikiId2, $draftIds);
        $this->assertNotContains($otherWikiId, $draftIds);
    }

    /**
     * 正常系：Statusで下書きWikiが取得できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByStatus(): void
    {
        $wikiId1 = StrTestHelper::generateUuid();
        $wikiId2 = StrTestHelper::generateUuid();
        $otherWikiId = StrTestHelper::generateUuid();

        CreateDraftWiki::create($wikiId1, 'group', [
            'slug' => 'gr-pending-wiki-1',
            'status' => ApprovalStatus::Pending->value,
        ]);
        CreateDraftWiki::create($wikiId2, 'talent', [
            'slug' => 'tl-pending-wiki-2',
            'status' => ApprovalStatus::Pending->value,
        ]);
        CreateDraftWiki::create($otherWikiId, 'group', [
            'slug' => 'gr-approved-wiki',
            'status' => ApprovalStatus::Approved->value,
            'approver_id' => StrTestHelper::generateUuid(),
        ]);

        $repository = $this->app->make(DraftWikiRepositoryInterface::class);
        $drafts = $repository->findByStatus(ApprovalStatus::Pending);

        $this->assertCount(2, $drafts);
        $draftIds = array_map(static fn (DraftWiki $draft): string => (string) $draft->wikiIdentifier(), $drafts);
        $this->assertContains($wikiId1, $draftIds);
        $this->assertContains($wikiId2, $draftIds);
        $this->assertNotContains($otherWikiId, $draftIds);
    }

    /**
     * 正常系：ResourceTypeで下書きWikiが取得できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByResourceType(): void
    {
        $wikiId1 = StrTestHelper::generateUuid();
        $wikiId2 = StrTestHelper::generateUuid();
        $otherWikiId = StrTestHelper::generateUuid();

        CreateDraftWiki::create($wikiId1, 'group', [
            'slug' => 'gr-group-wiki-1',
        ]);
        CreateDraftWiki::create($wikiId2, 'group', [
            'slug' => 'gr-group-wiki-2',
        ]);
        CreateDraftWiki::create($otherWikiId, 'talent', [
            'slug' => 'tl-talent-wiki',
        ]);

        $repository = $this->app->make(DraftWikiRepositoryInterface::class);
        $drafts = $repository->findByResourceType(ResourceType::GROUP);

        $this->assertCount(2, $drafts);
        $draftIds = array_map(static fn (DraftWiki $draft): string => (string) $draft->wikiIdentifier(), $drafts);
        $this->assertContains($wikiId1, $draftIds);
        $this->assertContains($wikiId2, $draftIds);
        $this->assertNotContains($otherWikiId, $draftIds);
    }

    /**
     * 正常系：DraftWikiが正しく削除されること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testDelete(): void
    {
        $wikiId = StrTestHelper::generateUuid();

        CreateDraftWiki::create($wikiId, 'group', [
            'slug' => 'gr-delete-target',
        ]);

        $repository = $this->app->make(DraftWikiRepositoryInterface::class);
        $found = $repository->findById(new DraftWikiIdentifier($wikiId));

        $this->assertInstanceOf(DraftWiki::class, $found);

        $repository->delete($found);

        $this->assertDatabaseMissing('draft_wikis', ['id' => $wikiId]);
    }

    /**
     * 正常系：publishedWikiIdentifierとsourceEditorIdentifierが設定されたDraftWikiが正しく保存されること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveWithPublishedWikiIdAndSourceEditorId(): void
    {
        $wikiId = StrTestHelper::generateUuid();
        $publishedWikiId = StrTestHelper::generateUuid();
        $editorId = StrTestHelper::generateUuid();
        $sourceEditorId = StrTestHelper::generateUuid();
        $translationSetId = StrTestHelper::generateUuid();

        CreateWiki::create($publishedWikiId, 'group');

        $draftWiki = new DraftWiki(
            new DraftWikiIdentifier($wikiId),
            new WikiIdentifier($publishedWikiId),
            new TranslationSetIdentifier($translationSetId),
            new Slug('gr-twice-edit'),
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
            new SectionContentCollection([], allowBlocks: false),
            null,
            ApprovalStatus::Pending,
            new PrincipalIdentifier($editorId),
            null,
            null,
            new PrincipalIdentifier($sourceEditorId),
        );

        $repository = $this->app->make(DraftWikiRepositoryInterface::class);
        $repository->save($draftWiki);

        $this->assertDatabaseHas('draft_wikis', [
            'id' => $wikiId,
            'published_wiki_id' => $publishedWikiId,
            'source_editor_id' => $sourceEditorId,
            'editor_id' => $editorId,
        ]);
    }

    /**
     * 異常系：IMAGEタイプのDraftWikiをsaveしようとした場合、InvalidArgumentExceptionがスローされること.
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

        $draftWiki = new DraftWiki(
            new DraftWikiIdentifier($wikiId),
            null,
            new TranslationSetIdentifier($translationSetId),
            new Slug('gr-test-image'),
            Language::KOREAN,
            ResourceType::IMAGE,
            $basic,
            new SectionContentCollection([], allowBlocks: false),
            null,
            ApprovalStatus::Pending,
            new PrincipalIdentifier($editorId),
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('IMAGE resource type does not have a Basic.');

        $repository = $this->app->make(DraftWikiRepositoryInterface::class);
        $repository->save($draftWiki);
    }

    /**
     * 異常系：TalentBasicが存在しないTalentタイプのDraftWikiをfindByIdで取得した場合、InvalidArgumentExceptionがスローされること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdWithMissingTalentBasicThrowsException(): void
    {
        $wikiId = StrTestHelper::generateUuid();

        DB::table('draft_wikis')->insert([
            'id' => $wikiId,
            'published_wiki_id' => null,
            'translation_set_identifier' => StrTestHelper::generateUuid(),
            'slug' => 'tl-missing-talent-basic',
            'language' => 'ko',
            'resource_type' => 'talent',
            'sections' => json_encode([]),
            'status' => 'pending',
            'editor_id' => StrTestHelper::generateUuid(),
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('TalentBasic not found for DraftWiki.');

        $repository = $this->app->make(DraftWikiRepositoryInterface::class);
        $repository->findById(new DraftWikiIdentifier($wikiId));
    }

    /**
     * 異常系：GroupBasicが存在しないGroupタイプのDraftWikiをfindByIdで取得した場合、InvalidArgumentExceptionがスローされること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdWithMissingGroupBasicThrowsException(): void
    {
        $wikiId = StrTestHelper::generateUuid();

        DB::table('draft_wikis')->insert([
            'id' => $wikiId,
            'published_wiki_id' => null,
            'translation_set_identifier' => StrTestHelper::generateUuid(),
            'slug' => 'gr-missing-group-basic',
            'language' => 'ko',
            'resource_type' => 'group',
            'sections' => json_encode([]),
            'status' => 'pending',
            'editor_id' => StrTestHelper::generateUuid(),
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('GroupBasic not found for DraftWiki.');

        $repository = $this->app->make(DraftWikiRepositoryInterface::class);
        $repository->findById(new DraftWikiIdentifier($wikiId));
    }

    /**
     * 異常系：AgencyBasicが存在しないAgencyタイプのDraftWikiをfindByIdで取得した場合、InvalidArgumentExceptionがスローされること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdWithMissingAgencyBasicThrowsException(): void
    {
        $wikiId = StrTestHelper::generateUuid();

        DB::table('draft_wikis')->insert([
            'id' => $wikiId,
            'published_wiki_id' => null,
            'translation_set_identifier' => StrTestHelper::generateUuid(),
            'slug' => 'ag-missing-agency-basic',
            'language' => 'ko',
            'resource_type' => 'agency',
            'sections' => json_encode([]),
            'status' => 'pending',
            'editor_id' => StrTestHelper::generateUuid(),
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('AgencyBasic not found for DraftWiki.');

        $repository = $this->app->make(DraftWikiRepositoryInterface::class);
        $repository->findById(new DraftWikiIdentifier($wikiId));
    }

    /**
     * 異常系：SongBasicが存在しないSongタイプのDraftWikiをfindByIdで取得した場合、InvalidArgumentExceptionがスローされること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdWithMissingSongBasicThrowsException(): void
    {
        $wikiId = StrTestHelper::generateUuid();

        DB::table('draft_wikis')->insert([
            'id' => $wikiId,
            'published_wiki_id' => null,
            'translation_set_identifier' => StrTestHelper::generateUuid(),
            'slug' => 'sg-missing-song-basic',
            'language' => 'ko',
            'resource_type' => 'song',
            'sections' => json_encode([]),
            'status' => 'pending',
            'editor_id' => StrTestHelper::generateUuid(),
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('SongBasic not found for DraftWiki.');

        $repository = $this->app->make(DraftWikiRepositoryInterface::class);
        $repository->findById(new DraftWikiIdentifier($wikiId));
    }
}
