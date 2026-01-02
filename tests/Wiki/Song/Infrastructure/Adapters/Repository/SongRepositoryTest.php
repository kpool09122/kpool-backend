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
use Source\Wiki\Shared\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Song\Domain\Entity\Song;
use Source\Wiki\Song\Domain\Repository\SongRepositoryInterface;
use Source\Wiki\Song\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Song\Domain\ValueObject\Composer;
use Source\Wiki\Song\Domain\ValueObject\Lyricist;
use Source\Wiki\Song\Domain\ValueObject\Overview;
use Source\Wiki\Song\Domain\ValueObject\ReleaseDate;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;
use Source\Wiki\Song\Domain\ValueObject\SongName;
use Tests\Helper\CreateGroup;
use Tests\Helper\CreateSong;
use Tests\Helper\CreateTalent;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class SongRepositoryTest extends TestCase
{
    /**
     * 正常系：指定したIDの歌情報が取得できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindById(): void
    {
        $songId = StrTestHelper::generateUuid();
        $translationSetIdentifier = StrTestHelper::generateUuid();
        $agencyId = StrTestHelper::generateUuid();
        $groupId = StrTestHelper::generateUuid();
        $talentId = StrTestHelper::generateUuid();

        CreateGroup::create($groupId);
        CreateTalent::create($talentId);

        CreateSong::create($songId, [
            'translation_set_identifier' => $translationSetIdentifier,
            'language' => Language::KOREAN->value,
            'name' => '소리꾼',
            'agency_id' => $agencyId,
            'group_id' => $groupId,
            'talent_id' => $talentId,
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
        $this->assertSame($groupId, (string) $song->groupIdentifier());
        $this->assertSame($talentId, (string) $song->talentIdentifier());
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
    #[Group('useDb')]
    public function testFindByIdWhenReleaseDateIsNull(): void
    {
        $songId = StrTestHelper::generateUuid();

        CreateSong::create($songId, [
            'language' => Language::KOREAN->value,
            'name' => 'Unreleased Track',
            'release_date' => null,
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
    #[Group('useDb')]
    public function testFindByIdWhenNotExist(): void
    {
        $repository = $this->app->make(SongRepositoryInterface::class);
        $song = $repository->findById(new SongIdentifier(StrTestHelper::generateUuid()));

        $this->assertNull($song);
    }

    /**
     * 正常系：正しく歌情報を保存できること.
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

        $song = new Song(
            new SongIdentifier(StrTestHelper::generateUuid()),
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            Language::KOREAN,
            new SongName('CASE 143'),
            new AgencyIdentifier(StrTestHelper::generateUuid()),
            new GroupIdentifier($groupId),
            new TalentIdentifier($talentId),
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

        $this->assertDatabaseHas('song_group', [
            'song_id' => (string) $song->songIdentifier(),
            'group_id' => $groupId,
        ]);

        $this->assertDatabaseHas('song_talent', [
            'song_id' => (string) $song->songIdentifier(),
            'talent_id' => $talentId,
        ]);
    }

    /**
     * 正常系：翻訳セットIDで歌情報一覧が取得できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByTranslationSetIdentifier(): void
    {
        $translationSetIdentifier = StrTestHelper::generateUuid();
        $groupId = StrTestHelper::generateUuid();
        $talentId = StrTestHelper::generateUuid();

        CreateGroup::create($groupId);
        CreateTalent::create($talentId);

        // 韓国語版
        $songIdKo = StrTestHelper::generateUuid();
        CreateSong::create($songIdKo, [
            'translation_set_identifier' => $translationSetIdentifier,
            'language' => Language::KOREAN->value,
            'name' => '소리꾼',
            'group_id' => $groupId,
            'talent_id' => $talentId,
            'lyricist' => 'Bang Chan, Changbin, Han',
            'composer' => 'Bang Chan, Changbin, Han',
            'overview' => 'Stray Kids 2nd full album NOEASY title track.',
            'version' => 2,
        ]);

        // 日本語版
        $songIdJa = StrTestHelper::generateUuid();
        CreateSong::create($songIdJa, [
            'translation_set_identifier' => $translationSetIdentifier,
            'language' => Language::JAPANESE->value,
            'name' => 'ソリックン',
            'group_id' => $groupId,
            'talent_id' => $talentId,
            'lyricist' => 'Bang Chan, Changbin, Han',
            'composer' => 'Bang Chan, Changbin, Han',
            'overview' => 'Stray Kids 2nd full album NOEASY title track.',
            'version' => 2,
        ]);

        // 別の翻訳セットの歌（取得されないはず）
        $otherSongId = StrTestHelper::generateUuid();
        CreateSong::create($otherSongId, [
            'translation_set_identifier' => StrTestHelper::generateUuid(),
            'language' => Language::KOREAN->value,
            'name' => 'CASE 143',
            'lyricist' => '3RACHA',
            'composer' => '3RACHA, Versachoi',
            'overview' => 'Stray Kids 7th mini album MAXIDENT title track.',
            'version' => 1,
        ]);

        $repository = $this->app->make(SongRepositoryInterface::class);
        $songs = $repository->findByTranslationSetIdentifier(
            new TranslationSetIdentifier($translationSetIdentifier)
        );

        $this->assertCount(2, $songs);
        $songIds = array_map(fn (Song $song) => (string) $song->songIdentifier(), $songs);
        $this->assertContains($songIdKo, $songIds);
        $this->assertContains($songIdJa, $songIds);
    }

    /**
     * 正常系：翻訳セットIDに該当する歌が存在しない場合、空の配列が返却されること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByTranslationSetIdentifierWhenNoSongs(): void
    {
        $repository = $this->app->make(SongRepositoryInterface::class);
        $songs = $repository->findByTranslationSetIdentifier(
            new TranslationSetIdentifier(StrTestHelper::generateUuid())
        );

        $this->assertIsArray($songs);
        $this->assertEmpty($songs);
    }
}
