<?php

declare(strict_types=1);

namespace Tests\Wiki\Song\Infrastructure\Adapters\Repository;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Song\Domain\Entity\DraftSong;
use Source\Wiki\Song\Domain\Repository\DraftSongRepositoryInterface;
use Source\Wiki\Song\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Song\Domain\ValueObject\Composer;
use Source\Wiki\Song\Domain\ValueObject\Lyricist;
use Source\Wiki\Song\Domain\ValueObject\Overview;
use Source\Wiki\Song\Domain\ValueObject\ReleaseDate;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;
use Source\Wiki\Song\Domain\ValueObject\SongName;
use Tests\Helper\CreateDraftSong;
use Tests\Helper\CreateGroup;
use Tests\Helper\CreateTalent;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class DraftSongRepositoryTest extends TestCase
{
    /**
     * 正常系：指定したIDの下書き歌情報が取得できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindById(): void
    {
        $draftId = StrTestHelper::generateUuid();
        $publishedId = StrTestHelper::generateUuid();
        $translationSetIdentifier = StrTestHelper::generateUuid();
        $editorId = StrTestHelper::generateUuid();
        $agencyId = StrTestHelper::generateUuid();
        $groupId = StrTestHelper::generateUuid();
        $talentId = StrTestHelper::generateUuid();

        CreateGroup::create($groupId);
        CreateTalent::create($talentId);

        CreateDraftSong::create($draftId, [
            'published_id' => $publishedId,
            'translation_set_identifier' => $translationSetIdentifier,
            'editor_id' => $editorId,
            'language' => Language::JAPANESE->value,
            'name' => 'Feel Special',
            'normalized_name' => 'feel special',
            'agency_id' => $agencyId,
            'group_id' => $groupId,
            'talent_id' => $talentId,
            'lyricist' => 'J.Y. Park',
            'normalized_lyricist' => 'j.y. park',
            'composer' => 'J.Y. Park',
            'normalized_composer' => 'j.y. park',
            'release_date' => '2019-09-23',
            'overview' => 'TWICE 8th mini album title track.',
            'cover_image_path' => '/images/songs/twice-feelspecial.jpg',
            'music_video_link' => 'https://www.youtube.com/watch?v=3ymwOvzhwHs',
            'status' => ApprovalStatus::Pending->value,
        ]);

        $repository = $this->app->make(DraftSongRepositoryInterface::class);
        $draft = $repository->findById(new SongIdentifier($draftId));

        $this->assertInstanceOf(DraftSong::class, $draft);
        $this->assertSame($draftId, (string) $draft->songIdentifier());
        $this->assertSame($publishedId, (string) $draft->publishedSongIdentifier());
        $this->assertSame($translationSetIdentifier, (string) $draft->translationSetIdentifier());
        $this->assertSame($editorId, (string) $draft->editorIdentifier());
        $this->assertSame(Language::JAPANESE, $draft->language());
        $this->assertSame('Feel Special', (string) $draft->name());
        $this->assertSame('feel special', $draft->normalizedName());
        $this->assertSame($agencyId, (string) $draft->agencyIdentifier());
        $this->assertSame($groupId, (string) $draft->groupIdentifier());
        $this->assertSame($talentId, (string) $draft->talentIdentifier());
        $this->assertSame('J.Y. Park', (string) $draft->lyricist());
        $this->assertSame('j.y. park', $draft->normalizedLyricist());
        $this->assertSame('J.Y. Park', (string) $draft->composer());
        $this->assertSame('j.y. park', $draft->normalizedComposer());
        $this->assertInstanceOf(ReleaseDate::class, $draft->releaseDate());
        $this->assertInstanceOf(DateTimeImmutable::class, $draft->releaseDate()->value());
        $this->assertSame('2019-09-23', $draft->releaseDate()->format('Y-m-d'));
        $this->assertSame('TWICE 8th mini album title track.', (string) $draft->overView());
        $this->assertSame('/images/songs/twice-feelspecial.jpg', (string) $draft->coverImagePath());
        $this->assertSame('https://www.youtube.com/watch?v=3ymwOvzhwHs', (string) $draft->musicVideoLink());
        $this->assertSame(ApprovalStatus::Pending, $draft->status());
    }

    /**
     * 正常系：下書きのリリース日が未設定の場合はnullが返却されること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdWhenReleaseDateIsNull(): void
    {
        $draftId = StrTestHelper::generateUuid();

        CreateDraftSong::create($draftId, [
            'language' => Language::KOREAN->value,
            'name' => 'Super Shy',
            'release_date' => null,
            'lyricist' => '250',
            'composer' => '250',
            'overview' => 'NewJeans upcoming single.',
        ]);

        $repository = $this->app->make(DraftSongRepositoryInterface::class);
        $draft = $repository->findById(new SongIdentifier($draftId));

        $this->assertInstanceOf(DraftSong::class, $draft);
        $this->assertNull($draft->releaseDate());
    }

    /**
     * 正常系：指定したIDの下書き歌情報が存在しない場合、nullが返却されること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdWhenNotExist(): void
    {
        $repository = $this->app->make(DraftSongRepositoryInterface::class);
        $draft = $repository->findById(new SongIdentifier(StrTestHelper::generateUuid()));

        $this->assertNull($draft);
    }

    /**
     * 正常系：正しく下書き歌を保存できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSave(): void
    {
        $groupId = StrTestHelper::generateUuid();
        $talentId = StrTestHelper::generateUuid();

        CreateGroup::create($groupId);
        CreateTalent::create($talentId);

        $draft = new DraftSong(
            new SongIdentifier(StrTestHelper::generateUuid()),
            new SongIdentifier(StrTestHelper::generateUuid()),
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            Language::KOREAN,
            new SongName('Attention'),
            'attention',
            new AgencyIdentifier(StrTestHelper::generateUuid()),
            new GroupIdentifier($groupId),
            new TalentIdentifier($talentId),
            new Lyricist('Gigi'),
            'gigi',
            new Composer('250'),
            '250',
            new ReleaseDate(new DateTimeImmutable('2022-07-22')),
            new Overview('NewJeans debut single.'),
            new ImagePath('/images/songs/newjeans-attention.jpg'),
            new ExternalContentLink('https://www.youtube.com/watch?v=js1CtxSY38I'),
            ApprovalStatus::UnderReview,
        );

        $repository = $this->app->make(DraftSongRepositoryInterface::class);
        $repository->save($draft);

        $this->assertDatabaseHas('draft_songs', [
            'id' => (string) $draft->songIdentifier(),
            'published_id' => (string) $draft->publishedSongIdentifier(),
            'translation_set_identifier' => (string) $draft->translationSetIdentifier(),
            'editor_id' => (string) $draft->editorIdentifier(),
            'language' => $draft->language()->value,
            'name' => (string) $draft->name(),
            'normalized_name' => $draft->normalizedName(),
            'agency_id' => (string) $draft->agencyIdentifier(),
            'lyricist' => (string) $draft->lyricist(),
            'normalized_lyricist' => $draft->normalizedLyricist(),
            'composer' => (string) $draft->composer(),
            'normalized_composer' => $draft->normalizedComposer(),
            'release_date' => $draft->releaseDate()?->format('Y-m-d'),
            'overview' => (string) $draft->overView(),
            'cover_image_path' => (string) $draft->coverImagePath(),
            'music_video_link' => (string) $draft->musicVideoLink(),
            'status' => $draft->status()->value,
        ]);

        $this->assertDatabaseHas('draft_song_group', [
            'draft_song_id' => (string) $draft->songIdentifier(),
            'group_id' => $groupId,
        ]);

        $this->assertDatabaseHas('draft_song_talent', [
            'draft_song_id' => (string) $draft->songIdentifier(),
            'talent_id' => $talentId,
        ]);
    }

    /**
     * 正常系：正しく下書き歌を削除できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testDelete(): void
    {
        $id = StrTestHelper::generateUuid();
        $groupId = StrTestHelper::generateUuid();
        $talentId = StrTestHelper::generateUuid();

        CreateGroup::create($groupId);
        CreateTalent::create($talentId);

        $draft = new DraftSong(
            new SongIdentifier($id),
            new SongIdentifier(StrTestHelper::generateUuid()),
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            Language::KOREAN,
            new SongName('Next Level'),
            'next level',
            new AgencyIdentifier(StrTestHelper::generateUuid()),
            new GroupIdentifier($groupId),
            new TalentIdentifier($talentId),
            new Lyricist('Kenzie'),
            'kenzie',
            new Composer('Dem Jointz'),
            'dem jointz',
            new ReleaseDate(new DateTimeImmutable('2021-05-17')),
            new Overview('aespa 2nd single.'),
            null,
            null,
            ApprovalStatus::Pending,
        );

        CreateDraftSong::create($id, [
            'published_id' => (string) $draft->publishedSongIdentifier(),
            'translation_set_identifier' => (string) $draft->translationSetIdentifier(),
            'editor_id' => (string) $draft->editorIdentifier(),
            'language' => $draft->language()->value,
            'name' => (string) $draft->name(),
            'normalized_name' => $draft->normalizedName(),
            'agency_id' => (string) $draft->agencyIdentifier(),
            'group_id' => $groupId,
            'talent_id' => $talentId,
            'lyricist' => (string) $draft->lyricist(),
            'normalized_lyricist' => $draft->normalizedLyricist(),
            'composer' => (string) $draft->composer(),
            'normalized_composer' => $draft->normalizedComposer(),
            'release_date' => $draft->releaseDate()?->format('Y-m-d'),
            'overview' => (string) $draft->overView(),
            'status' => $draft->status()->value,
        ]);

        $repository = $this->app->make(DraftSongRepositoryInterface::class);
        $repository->delete($draft);

        $this->assertDatabaseMissing('draft_songs', [
            'id' => $id,
        ]);

        $this->assertDatabaseMissing('draft_song_group', [
            'draft_song_id' => $id,
            'group_id' => $groupId,
        ]);

        $this->assertDatabaseMissing('draft_song_talent', [
            'draft_song_id' => $id,
            'talent_id' => $talentId,
        ]);
    }

    /**
     * 正常系：指定した翻訳セットIDに紐づく下書き歌が取得できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByTranslationSet(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $groupId = StrTestHelper::generateUuid();
        $talentId = StrTestHelper::generateUuid();

        CreateGroup::create($groupId);
        CreateTalent::create($talentId);

        $draft1Id = StrTestHelper::generateUuid();
        $draft1 = [
            'published_id' => StrTestHelper::generateUuid(),
            'translation_set_identifier' => (string) $translationSetIdentifier,
            'editor_id' => StrTestHelper::generateUuid(),
            'language' => Language::KOREAN->value,
            'name' => '신메뉴',
            'agency_id' => StrTestHelper::generateUuid(),
            'group_id' => $groupId,
            'talent_id' => $talentId,
            'lyricist' => 'Bang Chan, Changbin, Han',
            'composer' => 'Bang Chan, Changbin, Han',
            'release_date' => '2020-06-17',
            'overview' => 'Stray Kids 1st full album GO生 title track.',
            'status' => ApprovalStatus::Pending->value,
        ];

        $draft2Id = StrTestHelper::generateUuid();
        $draft2 = [
            'published_id' => StrTestHelper::generateUuid(),
            'translation_set_identifier' => (string) $translationSetIdentifier,
            'editor_id' => StrTestHelper::generateUuid(),
            'language' => Language::JAPANESE->value,
            'name' => '神メニュー',
            'agency_id' => StrTestHelper::generateUuid(),
            'group_id' => $groupId,
            'talent_id' => $talentId,
            'lyricist' => 'Bang Chan, Changbin, Han',
            'composer' => 'Bang Chan, Changbin, Han',
            'release_date' => '2020-06-17',
            'overview' => 'Stray Kids 1st full album GO生 title track Japanese translation.',
            'status' => ApprovalStatus::Approved->value,
        ];

        $otherDraftId = StrTestHelper::generateUuid();
        $otherDraft = [
            'published_id' => StrTestHelper::generateUuid(),
            'translation_set_identifier' => StrTestHelper::generateUuid(),
            'editor_id' => StrTestHelper::generateUuid(),
            'language' => Language::ENGLISH->value,
            'name' => 'LOVE DIVE',
            'agency_id' => StrTestHelper::generateUuid(),
            'lyricist' => 'Seo Ji-eum',
            'composer' => 'Ryan S. Jhun',
            'release_date' => '2022-04-05',
            'overview' => 'IVE 2nd single.',
            'status' => ApprovalStatus::Pending->value,
        ];

        CreateDraftSong::create($draft1Id, $draft1);
        CreateDraftSong::create($draft2Id, $draft2);
        CreateDraftSong::create($otherDraftId, $otherDraft);

        $repository = $this->app->make(DraftSongRepositoryInterface::class);
        $drafts = $repository->findByTranslationSet($translationSetIdentifier);

        $this->assertCount(2, $drafts);
        $draftIds = array_map(static fn (DraftSong $draft): string => (string) $draft->songIdentifier(), $drafts);
        $this->assertContains($draft1Id, $draftIds);
        $this->assertContains($draft2Id, $draftIds);
        $this->assertNotContains($otherDraftId, $draftIds);

        $releaseDates = [];
        foreach ($drafts as $draft) {
            $releaseDates[(string) $draft->songIdentifier()] = $draft->releaseDate()?->format('Y-m-d');
        }

        $this->assertSame('2020-06-17', $releaseDates[$draft1Id]);
        $this->assertSame('2020-06-17', $releaseDates[$draft2Id]);
    }

    /**
     * 正常系：翻訳セットIDに紐づく下書きのリリース日がnullの場合、nullが返却されること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByTranslationSetWhenReleaseDateIsNull(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $draftId = StrTestHelper::generateUuid();

        CreateDraftSong::create($draftId, [
            'translation_set_identifier' => (string) $translationSetIdentifier,
            'language' => Language::KOREAN->value,
            'name' => 'After LIKE',
            'release_date' => null,
            'lyricist' => 'Seo Ji-eum',
            'composer' => 'Ryan S. Jhun',
            'overview' => 'IVE 3rd single (release date TBD).',
        ]);

        $repository = $this->app->make(DraftSongRepositoryInterface::class);
        $drafts = $repository->findByTranslationSet($translationSetIdentifier);

        $this->assertCount(1, $drafts);
        $draft = $drafts[0];

        $this->assertSame($draftId, (string) $draft->songIdentifier());
        $this->assertNull($draft->releaseDate());
    }

    /**
     * 正常系：指定した翻訳セットIDに紐づく下書き歌が存在しない場合、空配列が返却されること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByTranslationSetWhenNotExist(): void
    {
        $repository = $this->app->make(DraftSongRepositoryInterface::class);
        $drafts = $repository->findByTranslationSet(
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
        );

        $this->assertIsArray($drafts);
        $this->assertEmpty($drafts);
    }
}
