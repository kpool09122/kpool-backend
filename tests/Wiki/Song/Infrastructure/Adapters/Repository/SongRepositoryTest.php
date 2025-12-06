<?php

declare(strict_types=1);

namespace Tests\Wiki\Song\Infrastructure\Adapters\Repository;

use DateTime;
use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\DB;
use JsonException;
use PHPUnit\Framework\Attributes\Group as PHPUnitGroup;
use ReflectionClass;
use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;
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
use Source\Wiki\Song\Infrastracture\Adapters\Repository\SongRepository;
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
        $id = StrTestHelper::generateUlid();
        $translationSetId = StrTestHelper::generateUlid();
        $translation = Language::ENGLISH;
        $name = 'Thunderous';
        $agencyId = StrTestHelper::generateUlid();
        $belongIdentifiers = [StrTestHelper::generateUlid(), StrTestHelper::generateUlid()];
        $lyricist = 'Han';
        $composer = 'Bang Chan';
        $releaseDate = '2021-08-23';
        $overview = 'Title track';
        $coverImagePath = '/images/song/thunderous.jpg';
        $musicVideoLink = 'https://example.com/mv';
        $version = 2;

        DB::table('songs')->upsert([
            'id' => $id,
            'translation_set_identifier' => $translationSetId,
            'language' => $translation->value,
            'name' => $name,
            'agency_id' => $agencyId,
            'belong_identifiers' => json_encode($belongIdentifiers),
            'lyricist' => $lyricist,
            'composer' => $composer,
            'release_date' => $releaseDate,
            'overview' => $overview,
            'cover_image_path' => $coverImagePath,
            'music_video_link' => $musicVideoLink,
            'version' => $version,
        ], 'id');

        $repository = $this->app->make(SongRepositoryInterface::class);
        $song = $repository->findById(new SongIdentifier($id));

        $this->assertInstanceOf(Song::class, $song);
        $this->assertSame($id, (string) $song->songIdentifier());
        $this->assertSame($translationSetId, (string) $song->translationSetIdentifier());
        $this->assertSame($translation, $song->language());
        $this->assertSame($name, (string) $song->name());
        $this->assertSame($agencyId, (string) $song->agencyIdentifier());
        $this->assertSame($belongIdentifiers, array_map(
            static fn (BelongIdentifier $identifier): string => (string) $identifier,
            $song->belongIdentifiers(),
        ));
        $this->assertSame($lyricist, (string) $song->lyricist());
        $this->assertSame($composer, (string) $song->composer());
        $this->assertInstanceOf(ReleaseDate::class, $song->releaseDate());
        $this->assertInstanceOf(DateTimeImmutable::class, $song->releaseDate()->value());
        $this->assertSame($releaseDate, $song->releaseDate()->format('Y-m-d'));
        $this->assertSame($overview, (string) $song->overView());
        $this->assertSame($coverImagePath, (string) $song->coverImagePath());
        $this->assertSame($musicVideoLink, (string) $song->musicVideoLink());
        $this->assertSame($version, $song->version()->value());
    }

    /**
     * 正常系：リリース日が未設定の場合はnullが返却されること.
     *
     * @throws BindingResolutionException
     */
    public function testFindByIdWhenReleaseDateIsNull(): void
    {
        $id = StrTestHelper::generateUlid();

        DB::table('songs')->upsert([
            'id' => $id,
            'translation_set_identifier' => StrTestHelper::generateUlid(),
            'language' => Language::ENGLISH->value,
            'name' => 'No Release Date',
            'agency_id' => StrTestHelper::generateUlid(),
            'belong_identifiers' => json_encode([StrTestHelper::generateUlid()]),
            'lyricist' => 'Lyricist',
            'composer' => 'Composer',
            'release_date' => null,
            'overview' => 'Overview',
            'cover_image_path' => null,
            'music_video_link' => null,
            'version' => 1,
        ], 'id');

        $repository = $this->app->make(SongRepositoryInterface::class);
        $song = $repository->findById(new SongIdentifier($id));

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
        $song = $repository->findById(new SongIdentifier(StrTestHelper::generateUlid()));

        $this->assertNull($song);
    }

    /**
     * 正常系：指定したIDの下書き歌情報が取得できること.
     *
     * @throws BindingResolutionException
     */
    public function testFindDraftById(): void
    {
        $id = StrTestHelper::generateUlid();
        $publishedId = StrTestHelper::generateUlid();
        $translationSetId = StrTestHelper::generateUlid();
        $editorId = StrTestHelper::generateUlid();
        $translation = Language::JAPANESE;
        $name = 'サンプル歌';
        $agencyId = StrTestHelper::generateUlid();
        $belongIdentifiers = [StrTestHelper::generateUlid()];
        $lyricist = 'テスター';
        $composer = 'コンポーザー';
        $releaseDate = '2022-02-02';
        $overview = 'ドラフト概要';
        $coverImagePath = '/covers/sample.png';
        $musicVideoLink = 'https://example.com/draft';
        $status = ApprovalStatus::Pending;

        DB::table('draft_songs')->upsert([
            'id' => $id,
            'published_id' => $publishedId,
            'translation_set_identifier' => $translationSetId,
            'editor_id' => $editorId,
            'language' => $translation->value,
            'name' => $name,
            'agency_id' => $agencyId,
            'belong_identifiers' => json_encode($belongIdentifiers),
            'lyricist' => $lyricist,
            'composer' => $composer,
            'release_date' => $releaseDate,
            'overview' => $overview,
            'cover_image_path' => $coverImagePath,
            'music_video_link' => $musicVideoLink,
            'status' => $status->value,
        ], 'id');

        $repository = $this->app->make(SongRepositoryInterface::class);
        $draft = $repository->findDraftById(new SongIdentifier($id));

        $this->assertInstanceOf(DraftSong::class, $draft);
        $this->assertSame($id, (string) $draft->songIdentifier());
        $this->assertSame($publishedId, (string) $draft->publishedSongIdentifier());
        $this->assertSame($translationSetId, (string) $draft->translationSetIdentifier());
        $this->assertSame($editorId, (string) $draft->editorIdentifier());
        $this->assertSame($translation, $draft->language());
        $this->assertSame($name, (string) $draft->name());
        $this->assertSame($agencyId, (string) $draft->agencyIdentifier());
        $this->assertSame($belongIdentifiers, array_map(
            static fn (BelongIdentifier $identifier): string => (string) $identifier,
            $draft->belongIdentifiers(),
        ));
        $this->assertSame($lyricist, (string) $draft->lyricist());
        $this->assertSame($composer, (string) $draft->composer());
        $this->assertInstanceOf(ReleaseDate::class, $draft->releaseDate());
        $this->assertInstanceOf(DateTimeImmutable::class, $draft->releaseDate()->value());
        $this->assertSame($releaseDate, $draft->releaseDate()->format('Y-m-d'));
        $this->assertSame($overview, (string) $draft->overView());
        $this->assertSame($coverImagePath, (string) $draft->coverImagePath());
        $this->assertSame($musicVideoLink, (string) $draft->musicVideoLink());
        $this->assertSame($status, $draft->status());
    }

    /**
     * 正常系：下書きのリリース日が未設定の場合はnullが返却されること.
     *
     * @throws BindingResolutionException
     */
    public function testFindDraftByIdWhenReleaseDateIsNull(): void
    {
        $id = StrTestHelper::generateUlid();

        DB::table('draft_songs')->upsert([
            'id' => $id,
            'published_id' => StrTestHelper::generateUlid(),
            'translation_set_identifier' => StrTestHelper::generateUlid(),
            'editor_id' => StrTestHelper::generateUlid(),
            'language' => Language::JAPANESE->value,
            'name' => 'Draft Song',
            'agency_id' => StrTestHelper::generateUlid(),
            'belong_identifiers' => json_encode([StrTestHelper::generateUlid()]),
            'lyricist' => 'Tester',
            'composer' => 'Composer',
            'release_date' => null,
            'overview' => 'Draft Overview',
            'cover_image_path' => null,
            'music_video_link' => null,
            'status' => ApprovalStatus::Pending->value,
        ], 'id');

        $repository = $this->app->make(SongRepositoryInterface::class);
        $draft = $repository->findDraftById(new SongIdentifier($id));

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
        $draft = $repository->findDraftById(new SongIdentifier(StrTestHelper::generateUlid()));

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
            new SongIdentifier(StrTestHelper::generateUlid()),
            new TranslationSetIdentifier(StrTestHelper::generateUlid()),
            Language::KOREAN,
            new SongName('Case 143'),
            new AgencyIdentifier(StrTestHelper::generateUlid()),
            [
                new BelongIdentifier(StrTestHelper::generateUlid()),
                new BelongIdentifier(StrTestHelper::generateUlid()),
            ],
            new Lyricist('3racha'),
            new Composer('Versachoi'),
            new ReleaseDate(new DateTimeImmutable('2022-10-07')),
            new Overview('2nd mini title'),
            new ImagePath('/cover/case143.png'),
            new ExternalContentLink('https://example.com/case143'),
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
            new SongIdentifier(StrTestHelper::generateUlid()),
            new SongIdentifier(StrTestHelper::generateUlid()),
            new TranslationSetIdentifier(StrTestHelper::generateUlid()),
            new EditorIdentifier(StrTestHelper::generateUlid()),
            Language::JAPANESE,
            new SongName('ブルームーン'),
            new AgencyIdentifier(StrTestHelper::generateUlid()),
            [new BelongIdentifier(StrTestHelper::generateUlid())],
            new Lyricist('Lyricist'),
            new Composer('Composer'),
            new ReleaseDate(new DateTimeImmutable('2023-05-05')),
            new Overview('Draft overview'),
            new ImagePath('/cover/draft.png'),
            new ExternalContentLink('https://example.com/draft-song'),
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
        $id = StrTestHelper::generateUlid();
        $draft = new DraftSong(
            new SongIdentifier($id),
            new SongIdentifier(StrTestHelper::generateUlid()),
            new TranslationSetIdentifier(StrTestHelper::generateUlid()),
            new EditorIdentifier(StrTestHelper::generateUlid()),
            Language::ENGLISH,
            new SongName('Delete Draft'),
            new AgencyIdentifier(StrTestHelper::generateUlid()),
            [new BelongIdentifier(StrTestHelper::generateUlid())],
            new Lyricist('L'),
            new Composer('C'),
            new ReleaseDate(new DateTimeImmutable('2024-04-04')),
            new Overview('To delete'),
            null,
            null,
            ApprovalStatus::Pending,
        );

        DB::table('draft_songs')->insert([
            'id' => $id,
            'published_id' => (string) $draft->publishedSongIdentifier(),
            'translation_set_identifier' => (string) $draft->translationSetIdentifier(),
            'editor_id' => (string) $draft->editorIdentifier(),
            'language' => $draft->language()->value,
            'name' => (string) $draft->name(),
            'agency_id' => (string) $draft->agencyIdentifier(),
            'belong_identifiers' => json_encode([(string) $draft->belongIdentifiers()[0]]),
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
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());

        $draft1 = [
            'id' => StrTestHelper::generateUlid(),
            'published_id' => StrTestHelper::generateUlid(),
            'translation_set_identifier' => (string) $translationSetIdentifier,
            'editor_id' => StrTestHelper::generateUlid(),
            'language' => Language::KOREAN->value,
            'name' => '드래프트 송1',
            'agency_id' => StrTestHelper::generateUlid(),
            'belong_identifiers' => json_encode([StrTestHelper::generateUlid()]),
            'lyricist' => '가사',
            'composer' => '작곡',
            'release_date' => '2020-01-10',
            'overview' => '첫번째',
            'status' => ApprovalStatus::Pending->value,
        ];

        $draft2 = [
            'id' => StrTestHelper::generateUlid(),
            'published_id' => StrTestHelper::generateUlid(),
            'translation_set_identifier' => (string) $translationSetIdentifier,
            'editor_id' => StrTestHelper::generateUlid(),
            'language' => Language::JAPANESE->value,
            'name' => 'ドラフトソング2',
            'agency_id' => StrTestHelper::generateUlid(),
            'belong_identifiers' => json_encode([StrTestHelper::generateUlid()]),
            'lyricist' => '作詞',
            'composer' => '作曲',
            'release_date' => '2021-02-11',
            'overview' => '二件目',
            'status' => ApprovalStatus::Approved->value,
        ];

        $otherDraft = [
            'id' => StrTestHelper::generateUlid(),
            'published_id' => StrTestHelper::generateUlid(),
            'translation_set_identifier' => StrTestHelper::generateUlid(),
            'editor_id' => StrTestHelper::generateUlid(),
            'language' => Language::ENGLISH->value,
            'name' => 'Other Draft',
            'agency_id' => StrTestHelper::generateUlid(),
            'belong_identifiers' => json_encode([StrTestHelper::generateUlid()]),
            'lyricist' => 'Other lyricist',
            'composer' => 'Other composer',
            'release_date' => '2022-03-12',
            'overview' => '別翻訳セット',
            'status' => ApprovalStatus::Pending->value,
        ];

        DB::table('draft_songs')->insert([$draft1, $draft2, $otherDraft]);

        /** @var SongRepositoryInterface $repository */
        $repository = $this->app->make(SongRepositoryInterface::class);
        $drafts = $repository->findDraftsByTranslationSet($translationSetIdentifier);

        $this->assertCount(2, $drafts);
        $draftIds = array_map(static fn (DraftSong $draft): string => (string) $draft->songIdentifier(), $drafts);
        $this->assertContains($draft1['id'], $draftIds);
        $this->assertContains($draft2['id'], $draftIds);
        $this->assertNotContains($otherDraft['id'], $draftIds);

        $releaseDates = [];
        foreach ($drafts as $draft) {
            $releaseDates[(string) $draft->songIdentifier()] = $draft->releaseDate()?->format('Y-m-d');
        }

        $this->assertSame('2020-01-10', $releaseDates[$draft1['id']]);
        $this->assertSame('2021-02-11', $releaseDates[$draft2['id']]);
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
            new TranslationSetIdentifier(StrTestHelper::generateUlid()),
        );

        $this->assertIsArray($drafts);
        $this->assertEmpty($drafts);
    }

    /**
     * 正常系：DateTimeInterface 実装（Carbon）を渡した場合でも DateTimeImmutable に変換されること.
     */
    public function testCreateReleaseDateConvertsMutableInstance(): void
    {
        $repository = new SongRepository();
        $reflection = new ReflectionClass($repository);
        $method = $reflection->getMethod('createReleaseDate');
        $method->setAccessible(true);

        $mutableReleaseDate = new DateTime('2020-08-15');

        /** @var ReleaseDate $releaseDate */
        $releaseDate = $method->invoke($repository, $mutableReleaseDate);

        $this->assertInstanceOf(ReleaseDate::class, $releaseDate);
        $this->assertInstanceOf(DateTimeImmutable::class, $releaseDate->value());
        $this->assertSame('2020-08-15', $releaseDate->format('Y-m-d'));
    }

    /**
     * 正常系：DateTimeImmutable を渡した場合は同一インスタンスが利用されること.
     */
    public function testCreateReleaseDateKeepsImmutableInstance(): void
    {
        $repository = new SongRepository();
        $reflection = new ReflectionClass($repository);
        $method = $reflection->getMethod('createReleaseDate');
        $method->setAccessible(true);

        $immutableReleaseDate = new DateTimeImmutable('2018-04-30');

        /** @var ReleaseDate $releaseDate */
        $releaseDate = $method->invoke($repository, $immutableReleaseDate);

        $this->assertInstanceOf(ReleaseDate::class, $releaseDate);
        $this->assertSame($immutableReleaseDate, $releaseDate->value());
    }
}
