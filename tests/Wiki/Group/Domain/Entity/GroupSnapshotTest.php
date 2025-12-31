<?php

declare(strict_types=1);

namespace Tests\Wiki\Group\Domain\Entity;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Group\Domain\Entity\GroupSnapshot;
use Source\Wiki\Group\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Group\Domain\ValueObject\Description;
use Source\Wiki\Group\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Group\Domain\ValueObject\GroupName;
use Source\Wiki\Group\Domain\ValueObject\GroupSnapshotIdentifier;
use Source\Wiki\Group\Domain\ValueObject\SongIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class GroupSnapshotTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $data = $this->createDummyGroupSnapshot();
        $snapshot = $data->snapshot;

        $this->assertSame((string)$data->snapshotIdentifier, (string)$snapshot->snapshotIdentifier());
        $this->assertSame((string)$data->groupIdentifier, (string)$snapshot->groupIdentifier());
        $this->assertSame((string)$data->translationSetIdentifier, (string)$snapshot->translationSetIdentifier());
        $this->assertSame($data->language->value, $snapshot->language()->value);
        $this->assertSame((string)$data->name, (string)$snapshot->name());
        $this->assertSame($data->normalizedName, $snapshot->normalizedName());
        $this->assertSame((string)$data->agencyIdentifier, (string)$snapshot->agencyIdentifier());
        $this->assertSame((string)$data->description, (string)$snapshot->description());
        $this->assertSame($data->songIdentifiers, $snapshot->songIdentifiers());
        $this->assertSame((string)$data->imagePath, (string)$snapshot->imagePath());
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
        $snapshotIdentifier = new GroupSnapshotIdentifier(StrTestHelper::generateUuid());
        $groupIdentifier = new GroupIdentifier(StrTestHelper::generateUuid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $language = Language::KOREAN;
        $name = new GroupName('TWICE');
        $normalizedName = 'twice';
        $description = new Description('TWICE is a South Korean girl group.');
        $songIdentifiers = [];
        $version = new Version(1);
        $createdAt = new DateTimeImmutable('2024-01-01 00:00:00');

        $snapshot = new GroupSnapshot(
            $snapshotIdentifier,
            $groupIdentifier,
            $translationSetIdentifier,
            $language,
            $name,
            $normalizedName,
            null,
            $description,
            $songIdentifiers,
            null,
            $version,
            $createdAt,
        );

        $this->assertNull($snapshot->agencyIdentifier());
        $this->assertNull($snapshot->imagePath());
    }

    /**
     * ダミーのGroupSnapshotを作成するヘルパーメソッド
     *
     * @return GroupSnapshotTestData
     */
    private function createDummyGroupSnapshot(): GroupSnapshotTestData
    {
        $snapshotIdentifier = new GroupSnapshotIdentifier(StrTestHelper::generateUuid());
        $groupIdentifier = new GroupIdentifier(StrTestHelper::generateUuid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $language = Language::KOREAN;
        $name = new GroupName('TWICE');
        $normalizedName = 'twice';
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUuid());
        $description = new Description('TWICE is a South Korean girl group.');
        $songIdentifiers = [
            new SongIdentifier(StrTestHelper::generateUuid()),
            new SongIdentifier(StrTestHelper::generateUuid()),
        ];
        $imagePath = new ImagePath('/resources/public/images/twice.webp');
        $version = new Version(1);
        $createdAt = new DateTimeImmutable('2024-01-01 00:00:00');

        $snapshot = new GroupSnapshot(
            $snapshotIdentifier,
            $groupIdentifier,
            $translationSetIdentifier,
            $language,
            $name,
            $normalizedName,
            $agencyIdentifier,
            $description,
            $songIdentifiers,
            $imagePath,
            $version,
            $createdAt,
        );

        return new GroupSnapshotTestData(
            snapshotIdentifier: $snapshotIdentifier,
            groupIdentifier: $groupIdentifier,
            translationSetIdentifier: $translationSetIdentifier,
            language: $language,
            name: $name,
            normalizedName: $normalizedName,
            agencyIdentifier: $agencyIdentifier,
            description: $description,
            songIdentifiers: $songIdentifiers,
            imagePath: $imagePath,
            version: $version,
            createdAt: $createdAt,
            snapshot: $snapshot,
        );
    }
}

/**
 * テストデータを保持するクラス
 * @phpstan-type SongIdentifierList list<SongIdentifier>
 */
readonly class GroupSnapshotTestData
{
    /**
     * @param SongIdentifier[] $songIdentifiers
     */
    public function __construct(
        public GroupSnapshotIdentifier  $snapshotIdentifier,
        public GroupIdentifier          $groupIdentifier,
        public TranslationSetIdentifier $translationSetIdentifier,
        public Language                 $language,
        public GroupName                $name,
        public string                   $normalizedName,
        public AgencyIdentifier         $agencyIdentifier,
        public Description              $description,
        public array                    $songIdentifiers,
        public ImagePath                $imagePath,
        public Version                  $version,
        public DateTimeImmutable        $createdAt,
        public GroupSnapshot            $snapshot,
    ) {
    }
}
