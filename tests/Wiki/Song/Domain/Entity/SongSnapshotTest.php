<?php

declare(strict_types=1);

namespace Tests\Wiki\Song\Domain\Entity;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
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
        $this->assertSame($data->version->value(), $snapshot->version()->value());
        $this->assertSame($data->createdAt->format('Y-m-d H:i:s'), $snapshot->createdAt()->format('Y-m-d H:i:s'));
        $this->assertSame((string)$data->editorIdentifier, (string)$snapshot->editorIdentifier());
        $this->assertSame((string)$data->approverIdentifier, (string)$snapshot->approverIdentifier());
        $this->assertSame((string)$data->mergerIdentifier, (string)$snapshot->mergerIdentifier());
        $this->assertSame($data->mergedAt->format('Y-m-d H:i:s'), $snapshot->mergedAt()->format('Y-m-d H:i:s'));
        $this->assertSame((string)$data->sourceEditorIdentifier, (string)$snapshot->sourceEditorIdentifier());
        $this->assertSame($data->translatedAt->format('Y-m-d H:i:s'), $snapshot->translatedAt()->format('Y-m-d H:i:s'));
        $this->assertSame($data->approvedAt->format('Y-m-d H:i:s'), $snapshot->approvedAt()->format('Y-m-d H:i:s'));
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
        $slug = new Slug('test-song-snapshot');
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
            $slug,
            $language,
            $name,
            null,
            null,
            null,
            $lyricist,
            $composer,
            null,
            $overView,
            $version,
            $createdAt,
        );

        $this->assertNull($snapshot->agencyIdentifier());
        $this->assertNull($snapshot->groupIdentifier());
        $this->assertNull($snapshot->talentIdentifier());
        $this->assertNull($snapshot->releaseDate());
    }

    /**
     * 正常系: オプショナルなプロパティがnullでもインスタンスが生成されること
     *
     * @return void
     */
    public function test__constructWithNullOptionalProperties(): void
    {
        $snapshotIdentifier = new SongSnapshotIdentifier(StrTestHelper::generateUuid());
        $songIdentifier = new SongIdentifier(StrTestHelper::generateUuid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $slug = new Slug('test-song-snapshot');
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
            $slug,
            $language,
            $name,
            null,
            null,
            null,
            $lyricist,
            $composer,
            null,
            $overView,
            $version,
            $createdAt,
        );

        $this->assertNull($snapshot->editorIdentifier());
        $this->assertNull($snapshot->approverIdentifier());
        $this->assertNull($snapshot->mergerIdentifier());
        $this->assertNull($snapshot->mergedAt());
        $this->assertNull($snapshot->sourceEditorIdentifier());
        $this->assertNull($snapshot->translatedAt());
        $this->assertNull($snapshot->approvedAt());
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
        $slug = new Slug('test-song-snapshot-tt');
        $language = Language::KOREAN;
        $name = new SongName('TT');
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUuid());
        $groupIdentifier = new GroupIdentifier(StrTestHelper::generateUuid());
        $talentIdentifier = new TalentIdentifier(StrTestHelper::generateUuid());
        $lyricist = new Lyricist('블랙아이드필승');
        $composer = new Composer('Sam Lewis');
        $releaseDate = new ReleaseDate(new DateTimeImmutable('2016-10-24'));
        $overView = new Overview('TT is a song by TWICE.');
        $version = new Version(1);
        $createdAt = new DateTimeImmutable('2024-01-01 00:00:00');
        $editorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $approverIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $mergerIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $mergedAt = new DateTimeImmutable('2024-01-02 00:00:00');
        $sourceEditorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $translatedAt = new DateTimeImmutable('2024-01-03 00:00:00');
        $approvedAt = new DateTimeImmutable('2024-01-04 00:00:00');

        $snapshot = new SongSnapshot(
            $snapshotIdentifier,
            $songIdentifier,
            $translationSetIdentifier,
            $slug,
            $language,
            $name,
            $agencyIdentifier,
            $groupIdentifier,
            $talentIdentifier,
            $lyricist,
            $composer,
            $releaseDate,
            $overView,
            $version,
            $createdAt,
            $editorIdentifier,
            $approverIdentifier,
            $mergerIdentifier,
            $mergedAt,
            $sourceEditorIdentifier,
            $translatedAt,
            $approvedAt,
        );

        return new SongSnapshotTestData(
            snapshotIdentifier: $snapshotIdentifier,
            songIdentifier: $songIdentifier,
            translationSetIdentifier: $translationSetIdentifier,
            slug: $slug,
            language: $language,
            name: $name,
            agencyIdentifier: $agencyIdentifier,
            groupIdentifier: $groupIdentifier,
            talentIdentifier: $talentIdentifier,
            lyricist: $lyricist,
            composer: $composer,
            releaseDate: $releaseDate,
            overView: $overView,
            version: $version,
            createdAt: $createdAt,
            editorIdentifier: $editorIdentifier,
            approverIdentifier: $approverIdentifier,
            mergerIdentifier: $mergerIdentifier,
            mergedAt: $mergedAt,
            sourceEditorIdentifier: $sourceEditorIdentifier,
            translatedAt: $translatedAt,
            approvedAt: $approvedAt,
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
        public Slug                     $slug,
        public Language                 $language,
        public SongName                 $name,
        public AgencyIdentifier         $agencyIdentifier,
        public GroupIdentifier          $groupIdentifier,
        public TalentIdentifier         $talentIdentifier,
        public Lyricist                 $lyricist,
        public Composer                 $composer,
        public ReleaseDate              $releaseDate,
        public Overview                 $overView,
        public Version                  $version,
        public DateTimeImmutable        $createdAt,
        public PrincipalIdentifier      $editorIdentifier,
        public PrincipalIdentifier      $approverIdentifier,
        public PrincipalIdentifier      $mergerIdentifier,
        public DateTimeImmutable        $mergedAt,
        public PrincipalIdentifier      $sourceEditorIdentifier,
        public DateTimeImmutable        $translatedAt,
        public DateTimeImmutable        $approvedAt,
        public SongSnapshot             $snapshot,
    ) {
    }
}
