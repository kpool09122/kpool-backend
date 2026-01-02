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
use Source\Wiki\Song\Domain\Entity\SongSnapshot;
use Source\Wiki\Song\Domain\Repository\SongSnapshotRepositoryInterface;
use Source\Wiki\Song\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Song\Domain\ValueObject\Composer;
use Source\Wiki\Song\Domain\ValueObject\Lyricist;
use Source\Wiki\Song\Domain\ValueObject\Overview;
use Source\Wiki\Song\Domain\ValueObject\ReleaseDate;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;
use Source\Wiki\Song\Domain\ValueObject\SongName;
use Source\Wiki\Song\Domain\ValueObject\SongSnapshotIdentifier;
use Tests\Helper\CreateGroup;
use Tests\Helper\CreateSongSnapshot;
use Tests\Helper\CreateTalent;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class SongSnapshotRepositoryTest extends TestCase
{
    /**
     * 正常系：スナップショットを保存できること
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSave(): void
    {
        $snapshotId = StrTestHelper::generateUuid();
        $songId = StrTestHelper::generateUuid();
        $translationSetIdentifier = StrTestHelper::generateUuid();
        $language = Language::KOREAN;
        $name = 'TT';
        $agencyId = StrTestHelper::generateUuid();
        $groupId = StrTestHelper::generateUuid();
        $talentId = StrTestHelper::generateUuid();
        $lyricist = '블랙아이드필승';
        $composer = 'Sam Lewis';
        $releaseDate = new DateTimeImmutable('2016-10-24');
        $overview = 'TT is a song by TWICE.';
        $coverImagePath = '/resources/public/images/tt.webp';
        $musicVideoLink = 'https://example.youtube.com/watch?v=dQw4w9WgXcQ';
        $version = 1;
        $createdAt = new DateTimeImmutable('2024-01-01 00:00:00');

        CreateGroup::create($groupId);
        CreateTalent::create($talentId);

        $snapshot = new SongSnapshot(
            new SongSnapshotIdentifier($snapshotId),
            new SongIdentifier($songId),
            new TranslationSetIdentifier($translationSetIdentifier),
            $language,
            new SongName($name),
            new AgencyIdentifier($agencyId),
            new GroupIdentifier($groupId),
            new TalentIdentifier($talentId),
            new Lyricist($lyricist),
            new Composer($composer),
            new ReleaseDate($releaseDate),
            new Overview($overview),
            new ImagePath($coverImagePath),
            new ExternalContentLink($musicVideoLink),
            new Version($version),
            $createdAt,
        );

        $repository = $this->app->make(SongSnapshotRepositoryInterface::class);
        $repository->save($snapshot);

        $this->assertDatabaseHas('song_snapshots', [
            'id' => $snapshotId,
            'song_id' => $songId,
            'translation_set_identifier' => $translationSetIdentifier,
            'language' => $language->value,
            'name' => $name,
            'agency_id' => $agencyId,
            'lyricist' => $lyricist,
            'composer' => $composer,
            'overview' => $overview,
            'cover_image_path' => $coverImagePath,
            'music_video_link' => $musicVideoLink,
            'version' => $version,
        ]);

        $this->assertDatabaseHas('song_snapshot_group', [
            'song_snapshot_id' => $snapshotId,
            'group_id' => $groupId,
        ]);

        $this->assertDatabaseHas('song_snapshot_talent', [
            'song_snapshot_id' => $snapshotId,
            'talent_id' => $talentId,
        ]);
    }

    /**
     * 正常系：agencyIdentifierがnullでもスナップショットを保存できること
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveWithNullAgencyIdentifier(): void
    {
        $snapshotId = StrTestHelper::generateUuid();
        $songId = StrTestHelper::generateUuid();

        $snapshot = new SongSnapshot(
            new SongSnapshotIdentifier($snapshotId),
            new SongIdentifier($songId),
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            Language::KOREAN,
            new SongName('TT'),
            null,
            null,
            null,
            new Lyricist(''),
            new Composer(''),
            null,
            new Overview(''),
            null,
            null,
            new Version(1),
            new DateTimeImmutable('2024-01-01 00:00:00'),
        );

        $repository = $this->app->make(SongSnapshotRepositoryInterface::class);
        $repository->save($snapshot);

        $this->assertDatabaseHas('song_snapshots', [
            'id' => $snapshotId,
            'song_id' => $songId,
            'agency_id' => null,
            'release_date' => null,
            'cover_image_path' => null,
            'music_video_link' => null,
        ]);
    }

    /**
     * 正常系：指定したSongIDのスナップショット一覧が取得できること
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindBySongIdentifier(): void
    {
        $songId = StrTestHelper::generateUuid();
        $translationSetIdentifier = StrTestHelper::generateUuid();

        // バージョン1のスナップショット
        $snapshotId1 = StrTestHelper::generateUuid();
        CreateSongSnapshot::create($snapshotId1, [
            'song_id' => $songId,
            'translation_set_identifier' => $translationSetIdentifier,
            'name' => 'TT v1',
            'overview' => 'Overview v1',
            'version' => 1,
            'created_at' => '2024-01-01 00:00:00',
        ]);

        // バージョン2のスナップショット
        $snapshotId2 = StrTestHelper::generateUuid();
        CreateSongSnapshot::create($snapshotId2, [
            'song_id' => $songId,
            'translation_set_identifier' => $translationSetIdentifier,
            'name' => 'TT v2',
            'overview' => 'Overview v2',
            'version' => 2,
            'created_at' => '2024-01-02 00:00:00',
        ]);

        // 別のSongのスナップショット（取得されないはず）
        $otherSongId = StrTestHelper::generateUuid();
        $snapshotId3 = StrTestHelper::generateUuid();
        CreateSongSnapshot::create($snapshotId3, [
            'song_id' => $otherSongId,
            'name' => 'CHEER UP',
            'overview' => 'CHEER UP is a song by TWICE.',
        ]);

        $repository = $this->app->make(SongSnapshotRepositoryInterface::class);
        $snapshots = $repository->findBySongIdentifier(new SongIdentifier($songId));

        $this->assertCount(2, $snapshots);
        // バージョン降順で取得されること
        $this->assertSame(2, $snapshots[0]->version()->value());
        $this->assertSame(1, $snapshots[1]->version()->value());
    }

    /**
     * 正常系：該当するスナップショットが存在しない場合、空の配列が返却されること
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindBySongIdentifierWhenNoSnapshots(): void
    {
        $repository = $this->app->make(SongSnapshotRepositoryInterface::class);
        $snapshots = $repository->findBySongIdentifier(
            new SongIdentifier(StrTestHelper::generateUuid())
        );

        $this->assertIsArray($snapshots);
        $this->assertEmpty($snapshots);
    }

    /**
     * 正常系：指定したSongIDとバージョンのスナップショットが取得できること
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindBySongAndVersion(): void
    {
        $songId = StrTestHelper::generateUuid();
        $snapshotId = StrTestHelper::generateUuid();
        $translationSetIdentifier = StrTestHelper::generateUuid();
        $name = 'TT';
        $agencyId = StrTestHelper::generateUuid();
        $lyricist = '블랙아이드필승';
        $composer = 'Sam Lewis';
        $releaseDate = '2016-10-24';
        $overview = 'TT is a song by TWICE.';
        $coverImagePath = '/resources/public/images/tt.webp';
        $musicVideoLink = 'https://example.youtube.com/watch?v=dQw4w9WgXcQ';
        $version = 3;

        CreateSongSnapshot::create($snapshotId, [
            'song_id' => $songId,
            'translation_set_identifier' => $translationSetIdentifier,
            'name' => $name,
            'agency_id' => $agencyId,
            'lyricist' => $lyricist,
            'composer' => $composer,
            'release_date' => $releaseDate,
            'overview' => $overview,
            'cover_image_path' => $coverImagePath,
            'music_video_link' => $musicVideoLink,
            'version' => $version,
        ]);

        $repository = $this->app->make(SongSnapshotRepositoryInterface::class);
        $snapshot = $repository->findBySongAndVersion(
            new SongIdentifier($songId),
            new Version($version)
        );

        $this->assertNotNull($snapshot);
        $this->assertSame($snapshotId, (string)$snapshot->snapshotIdentifier());
        $this->assertSame($songId, (string)$snapshot->songIdentifier());
        $this->assertSame($translationSetIdentifier, (string)$snapshot->translationSetIdentifier());
        $this->assertSame(Language::KOREAN, $snapshot->language());
        $this->assertSame($name, (string)$snapshot->name());
        $this->assertSame($agencyId, (string)$snapshot->agencyIdentifier());
        $this->assertSame($lyricist, (string)$snapshot->lyricist());
        $this->assertSame($composer, (string)$snapshot->composer());
        $this->assertSame($overview, (string)$snapshot->overView());
        $this->assertSame($coverImagePath, (string)$snapshot->coverImagePath());
        $this->assertSame($musicVideoLink, (string)$snapshot->musicVideoLink());
        $this->assertSame($version, $snapshot->version()->value());
    }

    /**
     * 正常系：該当するスナップショットが存在しない場合、nullが返却されること
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindBySongAndVersionWhenNoSnapshot(): void
    {
        $repository = $this->app->make(SongSnapshotRepositoryInterface::class);
        $snapshot = $repository->findBySongAndVersion(
            new SongIdentifier(StrTestHelper::generateUuid()),
            new Version(1)
        );

        $this->assertNull($snapshot);
    }

    /**
     * 正常系：翻訳セットIDとバージョンでスナップショット一覧が取得できること
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByTranslationSetIdentifierAndVersion(): void
    {
        $translationSetIdentifier = StrTestHelper::generateUuid();
        $version = 2;

        // 韓国語版のスナップショット（バージョン2）
        $snapshotIdKo = StrTestHelper::generateUuid();
        $songIdKo = StrTestHelper::generateUuid();
        CreateSongSnapshot::create($snapshotIdKo, [
            'song_id' => $songIdKo,
            'translation_set_identifier' => $translationSetIdentifier,
            'language' => Language::KOREAN->value,
            'name' => 'TT v2',
            'overview' => 'TT is a song by TWICE (v2).',
            'version' => $version,
        ]);

        // 日本語版のスナップショット（バージョン2）
        $snapshotIdJa = StrTestHelper::generateUuid();
        $songIdJa = StrTestHelper::generateUuid();
        CreateSongSnapshot::create($snapshotIdJa, [
            'song_id' => $songIdJa,
            'translation_set_identifier' => $translationSetIdentifier,
            'language' => Language::JAPANESE->value,
            'name' => 'TT v2 JA',
            'overview' => 'TT is a song by TWICE (v2 JA).',
            'version' => $version,
        ]);

        // 同じ翻訳セットだが異なるバージョン（取得されないはず）
        $snapshotIdV1 = StrTestHelper::generateUuid();
        CreateSongSnapshot::create($snapshotIdV1, [
            'song_id' => $songIdKo,
            'translation_set_identifier' => $translationSetIdentifier,
            'language' => Language::KOREAN->value,
            'name' => 'TT v1',
            'overview' => 'TT is a song by TWICE (v1).',
            'version' => 1,
        ]);

        // 異なる翻訳セットのスナップショット（取得されないはず）
        $snapshotIdOther = StrTestHelper::generateUuid();
        CreateSongSnapshot::create($snapshotIdOther, [
            'song_id' => StrTestHelper::generateUuid(),
            'translation_set_identifier' => StrTestHelper::generateUuid(),
            'language' => Language::KOREAN->value,
            'name' => 'CHEER UP',
            'overview' => 'CHEER UP is a song by TWICE.',
            'version' => $version,
        ]);

        $repository = $this->app->make(SongSnapshotRepositoryInterface::class);
        $snapshots = $repository->findByTranslationSetIdentifierAndVersion(
            new TranslationSetIdentifier($translationSetIdentifier),
            new Version($version)
        );

        $this->assertCount(2, $snapshots);
        $snapshotIds = array_map(
            fn (SongSnapshot $snapshot) => (string) $snapshot->snapshotIdentifier(),
            $snapshots
        );
        $this->assertContains($snapshotIdKo, $snapshotIds);
        $this->assertContains($snapshotIdJa, $snapshotIds);
    }

    /**
     * 正常系：翻訳セットIDとバージョンに該当するスナップショットが存在しない場合、空の配列が返却されること
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByTranslationSetIdentifierAndVersionWhenNoSnapshots(): void
    {
        $repository = $this->app->make(SongSnapshotRepositoryInterface::class);
        $snapshots = $repository->findByTranslationSetIdentifierAndVersion(
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            new Version(1)
        );

        $this->assertIsArray($snapshots);
        $this->assertEmpty($snapshots);
    }
}
