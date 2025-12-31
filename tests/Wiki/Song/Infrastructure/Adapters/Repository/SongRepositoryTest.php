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
        $songData = $this->upsertSongRecord([
            'translation_set_identifier' => StrTestHelper::generateUuid(),
            'language' => Language::ENGLISH->value,
            'name' => 'Thunderous',
            'agency_id' => StrTestHelper::generateUuid(),
            'belong_identifiers' => [StrTestHelper::generateUuid(), StrTestHelper::generateUuid()],
            'lyricist' => 'Han',
            'composer' => 'Bang Chan',
            'release_date' => '2021-08-23',
            'overview' => 'Title track',
            'cover_image_path' => '/images/song/thunderous.jpg',
            'music_video_link' => 'https://example.com/mv',
            'version' => 2,
        ]);

        $repository = $this->app->make(SongRepositoryInterface::class);
        $song = $repository->findById(new SongIdentifier($songData['id']));

        $this->assertInstanceOf(Song::class, $song);
        $this->assertSame($songData['id'], (string) $song->songIdentifier());
        $this->assertSame($songData['translation_set_identifier'], (string) $song->translationSetIdentifier());
        $this->assertSame(Language::ENGLISH, $song->language());
        $this->assertSame($songData['name'], (string) $song->name());
        $this->assertSame($songData['agency_id'], (string) $song->agencyIdentifier());
        $this->assertSame($songData['belong_identifiers'], array_map(
            static fn (BelongIdentifier $identifier): string => (string) $identifier,
            $song->belongIdentifiers(),
        ));
        $this->assertSame($songData['lyricist'], (string) $song->lyricist());
        $this->assertSame($songData['composer'], (string) $song->composer());
        $this->assertInstanceOf(ReleaseDate::class, $song->releaseDate());
        $this->assertInstanceOf(DateTimeImmutable::class, $song->releaseDate()->value());
        $this->assertSame($songData['release_date'], $song->releaseDate()->format('Y-m-d'));
        $this->assertSame($songData['overview'], (string) $song->overView());
        $this->assertSame($songData['cover_image_path'], (string) $song->coverImagePath());
        $this->assertSame($songData['music_video_link'], (string) $song->musicVideoLink());
        $this->assertSame($songData['version'], $song->version()->value());
    }

    /**
     * 正常系：リリース日が未設定の場合はnullが返却されること.
     *
     * @throws BindingResolutionException
     */
    public function testFindByIdWhenReleaseDateIsNull(): void
    {
        $songData = $this->upsertSongRecord([
            'language' => Language::ENGLISH->value,
            'name' => 'No Release Date',
            'release_date' => null,
            'belong_identifiers' => [StrTestHelper::generateUuid()],
        ]);

        $repository = $this->app->make(SongRepositoryInterface::class);
        $song = $repository->findById(new SongIdentifier($songData['id']));

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
        $draftData = $this->upsertDraftSongRecord([
            'published_id' => StrTestHelper::generateUuid(),
            'translation_set_identifier' => StrTestHelper::generateUuid(),
            'editor_id' => StrTestHelper::generateUuid(),
            'language' => Language::JAPANESE->value,
            'name' => 'サンプル歌',
            'agency_id' => StrTestHelper::generateUuid(),
            'belong_identifiers' => [StrTestHelper::generateUuid()],
            'lyricist' => 'テスター',
            'composer' => 'コンポーザー',
            'release_date' => '2022-02-02',
            'overview' => 'ドラフト概要',
            'cover_image_path' => '/covers/sample.png',
            'music_video_link' => 'https://example.com/draft',
            'status' => ApprovalStatus::Pending->value,
        ]);

        $repository = $this->app->make(SongRepositoryInterface::class);
        $draft = $repository->findDraftById(new SongIdentifier($draftData['id']));

        $this->assertInstanceOf(DraftSong::class, $draft);
        $this->assertSame($draftData['id'], (string) $draft->songIdentifier());
        $this->assertSame($draftData['published_id'], (string) $draft->publishedSongIdentifier());
        $this->assertSame($draftData['translation_set_identifier'], (string) $draft->translationSetIdentifier());
        $this->assertSame($draftData['editor_id'], (string) $draft->editorIdentifier());
        $this->assertSame(Language::JAPANESE, $draft->language());
        $this->assertSame($draftData['name'], (string) $draft->name());
        $this->assertSame($draftData['agency_id'], (string) $draft->agencyIdentifier());
        $this->assertSame($draftData['belong_identifiers'], array_map(
            static fn (BelongIdentifier $identifier): string => (string) $identifier,
            $draft->belongIdentifiers(),
        ));
        $this->assertSame($draftData['lyricist'], (string) $draft->lyricist());
        $this->assertSame($draftData['composer'], (string) $draft->composer());
        $this->assertInstanceOf(ReleaseDate::class, $draft->releaseDate());
        $this->assertInstanceOf(DateTimeImmutable::class, $draft->releaseDate()->value());
        $this->assertSame($draftData['release_date'], $draft->releaseDate()->format('Y-m-d'));
        $this->assertSame($draftData['overview'], (string) $draft->overView());
        $this->assertSame($draftData['cover_image_path'], (string) $draft->coverImagePath());
        $this->assertSame($draftData['music_video_link'], (string) $draft->musicVideoLink());
        $this->assertSame(ApprovalStatus::Pending, $draft->status());
    }

    /**
     * 正常系：下書きのリリース日が未設定の場合はnullが返却されること.
     *
     * @throws BindingResolutionException
     */
    public function testFindDraftByIdWhenReleaseDateIsNull(): void
    {
        $draftData = $this->upsertDraftSongRecord([
            'language' => Language::JAPANESE->value,
            'name' => 'Draft Song',
            'release_date' => null,
            'belong_identifiers' => [StrTestHelper::generateUuid()],
            'lyricist' => 'Tester',
            'composer' => 'Composer',
            'overview' => 'Draft Overview',
        ]);

        $repository = $this->app->make(SongRepositoryInterface::class);
        $draft = $repository->findDraftById(new SongIdentifier($draftData['id']));

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
            new SongName('Case 143'),
            new AgencyIdentifier(StrTestHelper::generateUuid()),
            [
                new BelongIdentifier(StrTestHelper::generateUuid()),
                new BelongIdentifier(StrTestHelper::generateUuid()),
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
            new SongIdentifier(StrTestHelper::generateUuid()),
            new SongIdentifier(StrTestHelper::generateUuid()),
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            Language::JAPANESE,
            new SongName('ブルームーン'),
            new AgencyIdentifier(StrTestHelper::generateUuid()),
            [new BelongIdentifier(StrTestHelper::generateUuid())],
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
        $id = StrTestHelper::generateUuid();
        $draft = new DraftSong(
            new SongIdentifier($id),
            new SongIdentifier(StrTestHelper::generateUuid()),
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            Language::ENGLISH,
            new SongName('Delete Draft'),
            new AgencyIdentifier(StrTestHelper::generateUuid()),
            [new BelongIdentifier(StrTestHelper::generateUuid())],
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
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());

        $draft1 = [
            'id' => StrTestHelper::generateUuid(),
            'published_id' => StrTestHelper::generateUuid(),
            'translation_set_identifier' => (string) $translationSetIdentifier,
            'editor_id' => StrTestHelper::generateUuid(),
            'language' => Language::KOREAN->value,
            'name' => '드래프트 송1',
            'agency_id' => StrTestHelper::generateUuid(),
            'belong_identifiers' => json_encode([StrTestHelper::generateUuid()]),
            'lyricist' => '가사',
            'composer' => '작곡',
            'release_date' => '2020-01-10',
            'overview' => '첫번째',
            'status' => ApprovalStatus::Pending->value,
        ];

        $draft2 = [
            'id' => StrTestHelper::generateUuid(),
            'published_id' => StrTestHelper::generateUuid(),
            'translation_set_identifier' => (string) $translationSetIdentifier,
            'editor_id' => StrTestHelper::generateUuid(),
            'language' => Language::JAPANESE->value,
            'name' => 'ドラフトソング2',
            'agency_id' => StrTestHelper::generateUuid(),
            'belong_identifiers' => json_encode([StrTestHelper::generateUuid()]),
            'lyricist' => '作詞',
            'composer' => '作曲',
            'release_date' => '2021-02-11',
            'overview' => '二件目',
            'status' => ApprovalStatus::Approved->value,
        ];

        $otherDraft = [
            'id' => StrTestHelper::generateUuid(),
            'published_id' => StrTestHelper::generateUuid(),
            'translation_set_identifier' => StrTestHelper::generateUuid(),
            'editor_id' => StrTestHelper::generateUuid(),
            'language' => Language::ENGLISH->value,
            'name' => 'Other Draft',
            'agency_id' => StrTestHelper::generateUuid(),
            'belong_identifiers' => json_encode([StrTestHelper::generateUuid()]),
            'lyricist' => 'Other lyricist',
            'composer' => 'Other composer',
            'release_date' => '2022-03-12',
            'overview' => '別翻訳セット',
            'status' => ApprovalStatus::Pending->value,
        ];

        DB::table('draft_songs')->insert([$draft1, $draft2, $otherDraft]);

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
     * 正常系：翻訳セットIDに紐づく下書きのリリース日がnullの場合、nullが返却されること.
     *
     * @throws BindingResolutionException
     */
    public function testFindDraftsByTranslationSetWhenReleaseDateIsNull(): void
    {
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());

        $draftData = $this->upsertDraftSongRecord([
            'translation_set_identifier' => (string) $translationSetIdentifier,
            'language' => Language::ENGLISH->value,
            'name' => 'Draft without release date',
            'release_date' => null,
            'belong_identifiers' => [StrTestHelper::generateUuid()],
            'lyricist' => 'Lyricist',
            'composer' => 'Composer',
        ]);

        /** @var SongRepositoryInterface $repository */
        $repository = $this->app->make(SongRepositoryInterface::class);
        $drafts = $repository->findDraftsByTranslationSet($translationSetIdentifier);

        $this->assertCount(1, $drafts);
        $draft = $drafts[0];

        $this->assertSame($draftData['id'], (string) $draft->songIdentifier());
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

    /**
     * @param array<string,mixed> $override
     * @return array<string,mixed>
     */
    private function upsertSongRecord(array $override = []): array
    {
        $data = array_merge([
            'id' => StrTestHelper::generateUuid(),
            'translation_set_identifier' => StrTestHelper::generateUuid(),
            'language' => Language::ENGLISH->value,
            'name' => 'Song',
            'agency_id' => StrTestHelper::generateUuid(),
            'belong_identifiers' => [StrTestHelper::generateUuid()],
            'lyricist' => 'Lyricist',
            'composer' => 'Composer',
            'release_date' => '2024-01-01',
            'lyrics' => '',
            'overview' => 'Overview',
            'cover_image_path' => null,
            'music_video_link' => null,
            'version' => 1,
        ], $override);

        $record = $data;
        $record['belong_identifiers'] = json_encode($record['belong_identifiers']);

        DB::table('songs')->upsert($record, 'id');

        return $data;
    }

    /**
     * @param array<string,mixed> $override
     * @return array<string,mixed>
     */
    private function upsertDraftSongRecord(array $override = []): array
    {
        $data = array_merge([
            'id' => StrTestHelper::generateUuid(),
            'published_id' => StrTestHelper::generateUuid(),
            'translation_set_identifier' => StrTestHelper::generateUuid(),
            'editor_id' => StrTestHelper::generateUuid(),
            'language' => Language::JAPANESE->value,
            'name' => 'Draft Song',
            'agency_id' => StrTestHelper::generateUuid(),
            'belong_identifiers' => [StrTestHelper::generateUuid()],
            'lyricist' => 'Lyricist',
            'composer' => 'Composer',
            'release_date' => '2024-01-01',
            'lyrics' => '',
            'overview' => 'Draft Overview',
            'cover_image_path' => null,
            'music_video_link' => null,
            'status' => ApprovalStatus::Pending->value,
        ], $override);

        $record = $data;
        $record['belong_identifiers'] = json_encode($record['belong_identifiers']);

        DB::table('draft_songs')->upsert($record, 'id');

        return $data;
    }
}
