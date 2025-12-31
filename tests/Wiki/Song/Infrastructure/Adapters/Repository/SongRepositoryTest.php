<?php

declare(strict_types=1);

namespace Tests\Wiki\Song\Infrastructure\Adapters\Repository;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\DB;
use JsonException;
use PHPUnit\Framework\Attributes\Group as PHPUnitGroup;
use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Song\Domain\Entity\DraftSong;
use Source\Wiki\Song\Domain\Entity\Song;
use Source\Wiki\Song\Domain\Repository\SongRepositoryInterface;
use Source\Wiki\Song\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Song\Domain\ValueObject\BelongIdentifier;
use Source\Wiki\Song\Domain\ValueObject\Composer;
use Source\Wiki\Song\Domain\ValueObject\Lyricist;
use Source\Wiki\Song\Domain\ValueObject\Overview;
use Source\Wiki\Song\Domain\ValueObject\ReleaseDate;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;
use Source\Wiki\Song\Domain\ValueObject\SongName;
use Tests\Helper\CreateDraftSong;
use Tests\Helper\CreateSong;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[PHPUnitGroup('useDb')]
class SongRepositoryTest extends TestCase
{
    /**
     * 正常系：指定したIDの歌情報が取得できること.
     *
     * @throws BindingResolutionException
     */
    public function testFindById(): void
    {
        $songId = StrTestHelper::generateUuid();
        $translationSetIdentifier = StrTestHelper::generateUuid();
        $agencyId = StrTestHelper::generateUuid();
        $belongIdentifiers = [StrTestHelper::generateUuid(), StrTestHelper::generateUuid()];

        CreateSong::create($songId, [
            'translation_set_identifier' => $translationSetIdentifier,
            'language' => Language::KOREAN->value,
            'name' => '소리꾼',
            'agency_id' => $agencyId,
            'belong_identifiers' => $belongIdentifiers,
            'lyricist' => 'Bang Chan, Changbin, Han',
            'composer' => 'Bang Chan, Changbin, Han',
            'release_date' => '2021-08-23',
            'overview' => 'Stray Kids 2nd full album NOEASY title track.',
            'cover_image_path' => '/images/songs/straykids-thunderous.jpg',
            'music_video_link' => 'https://www.youtube.com/watch?v=EaswWiwMVs8',
            'version' => 2,
        ]);

        $repository = $this->app->make(SongRepositoryInterface::class);
        $song = $repository->findById(new SongIdentifier($songId));

        $this->assertInstanceOf(Song::class, $song);
        $this->assertSame($songId, (string) $song->songIdentifier());
        $this->assertSame($translationSetIdentifier, (string) $song->translationSetIdentifier());
        $this->assertSame(Language::KOREAN, $song->language());
        $this->assertSame('소리꾼', (string) $song->name());
        $this->assertSame($agencyId, (string) $song->agencyIdentifier());
        $this->assertSame($belongIdentifiers, array_map(
            static fn (BelongIdentifier $identifier): string => (string) $identifier,
            $song->belongIdentifiers(),
        ));
        $this->assertSame('Bang Chan, Changbin, Han', (string) $song->lyricist());
        $this->assertSame('Bang Chan, Changbin, Han', (string) $song->composer());
        $this->assertInstanceOf(ReleaseDate::class, $song->releaseDate());
        $this->assertInstanceOf(DateTimeImmutable::class, $song->releaseDate()->value());
        $this->assertSame('2021-08-23', $song->releaseDate()->format('Y-m-d'));
        $this->assertSame('Stray Kids 2nd full album NOEASY title track.', (string) $song->overView());
        $this->assertSame('/images/songs/straykids-thunderous.jpg', (string) $song->coverImagePath());
        $this->assertSame('https://www.youtube.com/watch?v=EaswWiwMVs8', (string) $song->musicVideoLink());
        $this->assertSame(2, $song->version()->value());
    }

    /**
     * 正常系：リリース日が未設定の場合はnullが返却されること.
     *
     * @throws BindingResolutionException
     */
    public function testFindByIdWhenReleaseDateIsNull(): void
    {
        $songId = StrTestHelper::generateUuid();

        CreateSong::create($songId, [
            'language' => Language::KOREAN->value,
            'name' => 'Unreleased Track',
            'release_date' => null,
            'belong_identifiers' => [StrTestHelper::generateUuid()],
            'lyricist' => 'J.Y. Park',
            'composer' => 'J.Y. Park',
            'overview' => 'Upcoming TWICE song.',
        ]);

        $repository = $this->app->make(SongRepositoryInterface::class);
        $song = $repository->findById(new SongIdentifier($songId));

        $this->assertInstanceOf(Song::class, $song);
        $this->assertNull($song->releaseDate());
    }

    /**
     * 正常系：指定したIDの歌情報が存在しない場合、nullが返却されること.
     *
     * @throws BindingResolutionException
     */
    public function testFindByIdWhenNotExist(): void
    {
        $repository = $this->app->make(SongRepositoryInterface::class);
        $song = $repository->findById(new SongIdentifier(StrTestHelper::generateUuid()));

        $this->assertNull($song);
    }

    /**
     * 正常系：指定したIDの下書き歌情報が取得できること.
     *
     * @throws BindingResolutionException
     */
    public function testFindDraftById(): void
    {
        $draftId = StrTestHelper::generateUuid();
        $publishedId = StrTestHelper::generateUuid();
        $translationSetIdentifier = StrTestHelper::generateUuid();
        $editorId = StrTestHelper::generateUuid();
        $agencyId = StrTestHelper::generateUuid();
        $belongIdentifiers = [StrTestHelper::generateUuid()];

        CreateDraftSong::create($draftId, [
            'published_id' => $publishedId,
            'translation_set_identifier' => $translationSetIdentifier,
            'editor_id' => $editorId,
            'language' => Language::JAPANESE->value,
            'name' => 'Feel Special',
            'agency_id' => $agencyId,
            'belong_identifiers' => $belongIdentifiers,
            'lyricist' => 'J.Y. Park',
            'composer' => 'J.Y. Park',
            'release_date' => '2019-09-23',
            'overview' => 'TWICE 8th mini album title track.',
            'cover_image_path' => '/images/songs/twice-feelspecial.jpg',
            'music_video_link' => 'https://www.youtube.com/watch?v=3ymwOvzhwHs',
            'status' => ApprovalStatus::Pending->value,
        ]);

        $repository = $this->app->make(SongRepositoryInterface::class);
        $draft = $repository->findDraftById(new SongIdentifier($draftId));

        $this->assertInstanceOf(DraftSong::class, $draft);
        $this->assertSame($draftId, (string) $draft->songIdentifier());
        $this->assertSame($publishedId, (string) $draft->publishedSongIdentifier());
        $this->assertSame($translationSetIdentifier, (string) $draft->translationSetIdentifier());
        $this->assertSame($editorId, (string) $draft->editorIdentifier());
        $this->assertSame(Language::JAPANESE, $draft->language());
        $this->assertSame('Feel Special', (string) $draft->name());
        $this->assertSame($agencyId, (string) $draft->agencyIdentifier());
        $this->assertSame($belongIdentifiers, array_map(
            static fn (BelongIdentifier $identifier): string => (string) $identifier,
            $draft->belongIdentifiers(),
        ));
        $this->assertSame('J.Y. Park', (string) $draft->lyricist());
        $this->assertSame('J.Y. Park', (string) $draft->composer());
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
    public function testFindDraftByIdWhenReleaseDateIsNull(): void
    {
        $draftId = StrTestHelper::generateUuid();

        CreateDraftSong::create($draftId, [
            'language' => Language::KOREAN->value,
            'name' => 'Super Shy',
            'release_date' => null,
            'belong_identifiers' => [StrTestHelper::generateUuid()],
            'lyricist' => '250',
            'composer' => '250',
            'overview' => 'NewJeans upcoming single.',
        ]);

        $repository = $this->app->make(SongRepositoryInterface::class);
        $draft = $repository->findDraftById(new SongIdentifier($draftId));

        $this->assertInstanceOf(DraftSong::class, $draft);
        $this->assertNull($draft->releaseDate());
    }

    /**
     * 正常系：指定したIDの下書き歌情報が存在しない場合、nullが返却されること.
     *
     * @throws BindingResolutionException
     */
    public function testFindDraftByIdWhenNotExist(): void
    {
        $repository = $this->app->make(SongRepositoryInterface::class);
        $draft = $repository->findDraftById(new SongIdentifier(StrTestHelper::generateUuid()));

        $this->assertNull($draft);
    }

    /**
     * 正常系：正しく歌情報を保存できること.
     *
     * @throws BindingResolutionException
     * @throws JsonException
     */
    public function testSave(): void
    {
        $song = new Song(
            new SongIdentifier(StrTestHelper::generateUuid()),
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            Language::KOREAN,
            new SongName('CASE 143'),
            new AgencyIdentifier(StrTestHelper::generateUuid()),
            [
                new BelongIdentifier(StrTestHelper::generateUuid()),
                new BelongIdentifier(StrTestHelper::generateUuid()),
            ],
            new Lyricist('3RACHA'),
            new Composer('3RACHA, Versachoi'),
            new ReleaseDate(new DateTimeImmutable('2022-10-07')),
            new Overview('Stray Kids 7th mini album MAXIDENT title track.'),
            new ImagePath('/images/songs/straykids-case143.jpg'),
            new ExternalContentLink('https://www.youtube.com/watch?v=jk6zLoynzHw'),
            new Version(4),
        );

        $repository = $this->app->make(SongRepositoryInterface::class);
        $repository->save($song);

        $expectedBelongs = array_map(
            static fn (BelongIdentifier $identifier): string => (string) $identifier,
            $song->belongIdentifiers(),
        );

        $this->assertDatabaseHas('songs', [
            'id' => (string) $song->songIdentifier(),
            'translation_set_identifier' => (string) $song->translationSetIdentifier(),
            'language' => $song->language()->value,
            'name' => (string) $song->name(),
            'agency_id' => (string) $song->agencyIdentifier(),
            'lyricist' => (string) $song->lyricist(),
            'composer' => (string) $song->composer(),
            'release_date' => $song->releaseDate()?->format('Y-m-d'),
            'overview' => (string) $song->overView(),
            'cover_image_path' => (string) $song->coverImagePath(),
            'music_video_link' => (string) $song->musicVideoLink(),
            'version' => $song->version()->value(),
        ]);

        $rawBelongs = DB::table('songs')
            ->where('id', (string) $song->songIdentifier())
            ->value('belong_identifiers');
        $decodedBelongs = json_decode((string) $rawBelongs, true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame($expectedBelongs, $decodedBelongs);
    }

    /**
     * 正常系：正しく下書き歌を保存できること.
     *
     * @throws BindingResolutionException
     * @throws JsonException
     */
    public function testSaveDraft(): void
    {
        $draft = new DraftSong(
            new SongIdentifier(StrTestHelper::generateUuid()),
            new SongIdentifier(StrTestHelper::generateUuid()),
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            Language::KOREAN,
            new SongName('Attention'),
            new AgencyIdentifier(StrTestHelper::generateUuid()),
            [new BelongIdentifier(StrTestHelper::generateUuid())],
            new Lyricist('Gigi'),
            new Composer('250'),
            new ReleaseDate(new DateTimeImmutable('2022-07-22')),
            new Overview('NewJeans debut single.'),
            new ImagePath('/images/songs/newjeans-attention.jpg'),
            new ExternalContentLink('https://www.youtube.com/watch?v=js1CtxSY38I'),
            ApprovalStatus::UnderReview,
        );

        $repository = $this->app->make(SongRepositoryInterface::class);
        $repository->saveDraft($draft);

        $expectedBelongs = array_map(
            static fn (BelongIdentifier $identifier): string => (string) $identifier,
            $draft->belongIdentifiers(),
        );

        $this->assertDatabaseHas('draft_songs', [
            'id' => (string) $draft->songIdentifier(),
            'published_id' => (string) $draft->publishedSongIdentifier(),
            'translation_set_identifier' => (string) $draft->translationSetIdentifier(),
            'editor_id' => (string) $draft->editorIdentifier(),
            'language' => $draft->language()->value,
            'name' => (string) $draft->name(),
            'agency_id' => (string) $draft->agencyIdentifier(),
            'lyricist' => (string) $draft->lyricist(),
            'composer' => (string) $draft->composer(),
            'release_date' => $draft->releaseDate()?->format('Y-m-d'),
            'overview' => (string) $draft->overView(),
            'cover_image_path' => (string) $draft->coverImagePath(),
            'music_video_link' => (string) $draft->musicVideoLink(),
            'status' => $draft->status()->value,
        ]);

        $rawBelongs = DB::table('draft_songs')
            ->where('id', (string) $draft->songIdentifier())
            ->value('belong_identifiers');
        $decodedBelongs = json_decode((string) $rawBelongs, true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame($expectedBelongs, $decodedBelongs);
    }

    /**
     * 正常系：正しく下書き歌を削除できること.
     *
     * @throws BindingResolutionException
     */
    public function testDeleteDraft(): void
    {
        $id = StrTestHelper::generateUuid();
        $draft = new DraftSong(
            new SongIdentifier($id),
            new SongIdentifier(StrTestHelper::generateUuid()),
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            Language::KOREAN,
            new SongName('Next Level'),
            new AgencyIdentifier(StrTestHelper::generateUuid()),
            [new BelongIdentifier(StrTestHelper::generateUuid())],
            new Lyricist('Kenzie'),
            new Composer('Dem Jointz'),
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
            'agency_id' => (string) $draft->agencyIdentifier(),
            'belong_identifiers' => [(string) $draft->belongIdentifiers()[0]],
            'lyricist' => (string) $draft->lyricist(),
            'composer' => (string) $draft->composer(),
            'release_date' => $draft->releaseDate()?->format('Y-m-d'),
            'overview' => (string) $draft->overView(),
            'status' => $draft->status()->value,
        ]);

        $repository = $this->app->make(SongRepositoryInterface::class);
        $repository->deleteDraft($draft);

        $this->assertDatabaseMissing('draft_songs', [
            'id' => $id,
        ]);
    }

    /**
     * 正常系：指定した翻訳セットIDに紐づく下書き歌が取得できること.
     *
     * @throws BindingResolutionException
     */
    public function testFindDraftsByTranslationSet(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());

        $draft1Id = StrTestHelper::generateUuid();
        $draft1 = [
            'published_id' => StrTestHelper::generateUuid(),
            'translation_set_identifier' => (string) $translationSetIdentifier,
            'editor_id' => StrTestHelper::generateUuid(),
            'language' => Language::KOREAN->value,
            'name' => '신메뉴',
            'agency_id' => StrTestHelper::generateUuid(),
            'belong_identifiers' => [StrTestHelper::generateUuid()],
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
            'belong_identifiers' => [StrTestHelper::generateUuid()],
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
            'belong_identifiers' => [StrTestHelper::generateUuid()],
            'lyricist' => 'Seo Ji-eum',
            'composer' => 'Ryan S. Jhun',
            'release_date' => '2022-04-05',
            'overview' => 'IVE 2nd single.',
            'status' => ApprovalStatus::Pending->value,
        ];

        CreateDraftSong::create($draft1Id, $draft1);
        CreateDraftSong::create($draft2Id, $draft2);
        CreateDraftSong::create($otherDraftId, $otherDraft);

        $repository = $this->app->make(SongRepositoryInterface::class);
        $drafts = $repository->findDraftsByTranslationSet($translationSetIdentifier);

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
    public function testFindDraftsByTranslationSetWhenReleaseDateIsNull(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $draftId = StrTestHelper::generateUuid();

        CreateDraftSong::create($draftId, [
            'translation_set_identifier' => (string) $translationSetIdentifier,
            'language' => Language::KOREAN->value,
            'name' => 'After LIKE',
            'release_date' => null,
            'belong_identifiers' => [StrTestHelper::generateUuid()],
            'lyricist' => 'Seo Ji-eum',
            'composer' => 'Ryan S. Jhun',
            'overview' => 'IVE 3rd single (release date TBD).',
        ]);

        /** @var SongRepositoryInterface $repository */
        $repository = $this->app->make(SongRepositoryInterface::class);
        $drafts = $repository->findDraftsByTranslationSet($translationSetIdentifier);

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
    public function testFindDraftsByTranslationSetWhenNotExist(): void
    {
        $repository = $this->app->make(SongRepositoryInterface::class);
        $drafts = $repository->findDraftsByTranslationSet(
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
        );

        $this->assertIsArray($drafts);
        $this->assertEmpty($drafts);
    }
}
