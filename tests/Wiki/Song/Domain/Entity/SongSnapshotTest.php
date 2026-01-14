<?php

declare(strict_types=1);

namespace Tests\Wiki\Song\Domain\Entity;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Song\Domain\Entity\SongSnapshot;
use Source\Wiki\Song\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Song\Domain\ValueObject\Composer;
use Source\Wiki\Song\Domain\ValueObject\Lyricist;
use Source\Wiki\Song\Domain\ValueObject\Overview;
use Source\Wiki\Song\Domain\ValueObject\ReleaseDate;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;
use Source\Wiki\Song\Domain\ValueObject\SongName;
use Source\Wiki\Song\Domain\ValueObject\SongSnapshotIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class SongSnapshotTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $data = $this->createDummySongSnapshot();
        $snapshot = $data->snapshot;

        $this->assertSame((string)$data->snapshotIdentifier, (string)$snapshot->snapshotIdentifier());
        $this->assertSame((string)$data->songIdentifier, (string)$snapshot->songIdentifier());
        $this->assertSame((string)$data->translationSetIdentifier, (string)$snapshot->translationSetIdentifier());
        $this->assertSame($data->language->value, $snapshot->language()->value);
        $this->assertSame((string)$data->name, (string)$snapshot->name());
        $this->assertSame((string)$data->agencyIdentifier, (string)$snapshot->agencyIdentifier());
        $this->assertSame((string)$data->groupIdentifier, (string)$snapshot->groupIdentifier());
        $this->assertSame((string)$data->talentIdentifier, (string)$snapshot->talentIdentifier());
        $this->assertSame((string)$data->lyricist, (string)$snapshot->lyricist());
        $this->assertSame((string)$data->composer, (string)$snapshot->composer());
        $this->assertSame($data->releaseDate->value(), $snapshot->releaseDate()->value());
        $this->assertSame((string)$data->overView, (string)$snapshot->overView());
        $this->assertSame((string)$data->musicVideoLink, (string)$snapshot->musicVideoLink());
        $this->assertSame($data->version->value(), $snapshot->version()->value());
        $this->assertSame($data->createdAt->format('Y-m-d H:i:s'), $snapshot->createdAt()->format('Y-m-d H:i:s'));
    }

    /**
     * 正常系: agencyIdentifierがnullでもインスタンスが生成されること
     *
     * @return void
     */
    public function test__constructWithNullAgencyIdentifier(): void
    {
        $snapshotIdentifier = new SongSnapshotIdentifier(StrTestHelper::generateUuid());
        $songIdentifier = new SongIdentifier(StrTestHelper::generateUuid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $language = Language::KOREAN;
        $name = new SongName('TT');
        $lyricist = new Lyricist('블랙아이드필승');
        $composer = new Composer('Sam Lewis');
        $overView = new Overview('A song about love.');
        $version = new Version(1);
        $createdAt = new DateTimeImmutable('2024-01-01 00:00:00');

        $snapshot = new SongSnapshot(
            $snapshotIdentifier,
            $songIdentifier,
            $translationSetIdentifier,
            $language,
            $name,
            null,
            null,
            null,
            $lyricist,
            $composer,
            null,
            $overView,
            null,
            $version,
            $createdAt,
        );

        $this->assertNull($snapshot->agencyIdentifier());
        $this->assertNull($snapshot->groupIdentifier());
        $this->assertNull($snapshot->talentIdentifier());
        $this->assertNull($snapshot->releaseDate());
        $this->assertNull($snapshot->musicVideoLink());
    }

    /**
     * ダミーのSongSnapshotを作成するヘルパーメソッド
     *
     * @return SongSnapshotTestData
     */
    private function createDummySongSnapshot(): SongSnapshotTestData
    {
        $snapshotIdentifier = new SongSnapshotIdentifier(StrTestHelper::generateUuid());
        $songIdentifier = new SongIdentifier(StrTestHelper::generateUuid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $language = Language::KOREAN;
        $name = new SongName('TT');
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUuid());
        $groupIdentifier = new GroupIdentifier(StrTestHelper::generateUuid());
        $talentIdentifier = new TalentIdentifier(StrTestHelper::generateUuid());
        $lyricist = new Lyricist('블랙아이드필승');
        $composer = new Composer('Sam Lewis');
        $releaseDate = new ReleaseDate(new DateTimeImmutable('2016-10-24'));
        $overView = new Overview('TT is a song by TWICE.');
        $musicVideoLink = new ExternalContentLink('https://example.youtube.com/watch?v=dQw4w9WgXcQ');
        $version = new Version(1);
        $createdAt = new DateTimeImmutable('2024-01-01 00:00:00');

        $snapshot = new SongSnapshot(
            $snapshotIdentifier,
            $songIdentifier,
            $translationSetIdentifier,
            $language,
            $name,
            $agencyIdentifier,
            $groupIdentifier,
            $talentIdentifier,
            $lyricist,
            $composer,
            $releaseDate,
            $overView,
            $musicVideoLink,
            $version,
            $createdAt,
        );

        return new SongSnapshotTestData(
            snapshotIdentifier: $snapshotIdentifier,
            songIdentifier: $songIdentifier,
            translationSetIdentifier: $translationSetIdentifier,
            language: $language,
            name: $name,
            agencyIdentifier: $agencyIdentifier,
            groupIdentifier: $groupIdentifier,
            talentIdentifier: $talentIdentifier,
            lyricist: $lyricist,
            composer: $composer,
            releaseDate: $releaseDate,
            overView: $overView,
            musicVideoLink: $musicVideoLink,
            version: $version,
            createdAt: $createdAt,
            snapshot: $snapshot,
        );
    }
}

/**
 * テストデータを保持するクラス
 */
readonly class SongSnapshotTestData
{
    public function __construct(
        public SongSnapshotIdentifier   $snapshotIdentifier,
        public SongIdentifier           $songIdentifier,
        public TranslationSetIdentifier $translationSetIdentifier,
        public Language                 $language,
        public SongName                 $name,
        public AgencyIdentifier         $agencyIdentifier,
        public GroupIdentifier          $groupIdentifier,
        public TalentIdentifier         $talentIdentifier,
        public Lyricist                 $lyricist,
        public Composer                 $composer,
        public ReleaseDate              $releaseDate,
        public Overview                 $overView,
        public ExternalContentLink      $musicVideoLink,
        public Version                  $version,
        public DateTimeImmutable        $createdAt,
        public SongSnapshot             $snapshot,
    ) {
    }
}
