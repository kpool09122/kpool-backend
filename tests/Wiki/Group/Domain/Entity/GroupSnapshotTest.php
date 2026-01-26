<?php

declare(strict_types=1);

namespace Tests\Wiki\Group\Domain\Entity;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Group\Domain\Entity\GroupSnapshot;
use Source\Wiki\Group\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Group\Domain\ValueObject\Description;
use Source\Wiki\Group\Domain\ValueObject\GroupName;
use Source\Wiki\Group\Domain\ValueObject\GroupSnapshotIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
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
        $snapshotIdentifier = new GroupSnapshotIdentifier(StrTestHelper::generateUuid());
        $groupIdentifier = new GroupIdentifier(StrTestHelper::generateUuid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $slug = new Slug('twice');
        $language = Language::KOREAN;
        $name = new GroupName('TWICE');
        $normalizedName = 'twice';
        $description = new Description('TWICE is a South Korean girl group.');
        $version = new Version(1);
        $createdAt = new DateTimeImmutable('2024-01-01 00:00:00');

        $snapshot = new GroupSnapshot(
            $snapshotIdentifier,
            $groupIdentifier,
            $translationSetIdentifier,
            $slug,
            $language,
            $name,
            $normalizedName,
            null,
            $description,
            $version,
            $createdAt,
        );

        $this->assertNull($snapshot->agencyIdentifier());
    }

    /**
     * 正常系: オプショナルなプロパティがnullでもインスタンスが生成されること
     *
     * @return void
     */
    public function test__constructWithNullOptionalProperties(): void
    {
        $snapshotIdentifier = new GroupSnapshotIdentifier(StrTestHelper::generateUuid());
        $groupIdentifier = new GroupIdentifier(StrTestHelper::generateUuid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $slug = new Slug('twice');
        $language = Language::KOREAN;
        $name = new GroupName('TWICE');
        $normalizedName = 'twice';
        $description = new Description('TWICE is a South Korean girl group.');
        $version = new Version(1);
        $createdAt = new DateTimeImmutable('2024-01-01 00:00:00');

        $snapshot = new GroupSnapshot(
            $snapshotIdentifier,
            $groupIdentifier,
            $translationSetIdentifier,
            $slug,
            $language,
            $name,
            $normalizedName,
            null,
            $description,
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
     * ダミーのGroupSnapshotを作成するヘルパーメソッド
     *
     * @return GroupSnapshotTestData
     */
    private function createDummyGroupSnapshot(): GroupSnapshotTestData
    {
        $snapshotIdentifier = new GroupSnapshotIdentifier(StrTestHelper::generateUuid());
        $groupIdentifier = new GroupIdentifier(StrTestHelper::generateUuid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $slug = new Slug('twice');
        $language = Language::KOREAN;
        $name = new GroupName('TWICE');
        $normalizedName = 'twice';
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUuid());
        $description = new Description('TWICE is a South Korean girl group.');
        $version = new Version(1);
        $createdAt = new DateTimeImmutable('2024-01-01 00:00:00');
        $editorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $approverIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $mergerIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $mergedAt = new DateTimeImmutable('2024-01-02 00:00:00');
        $sourceEditorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $translatedAt = new DateTimeImmutable('2024-01-03 00:00:00');
        $approvedAt = new DateTimeImmutable('2024-01-04 00:00:00');

        $snapshot = new GroupSnapshot(
            $snapshotIdentifier,
            $groupIdentifier,
            $translationSetIdentifier,
            $slug,
            $language,
            $name,
            $normalizedName,
            $agencyIdentifier,
            $description,
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

        return new GroupSnapshotTestData(
            snapshotIdentifier: $snapshotIdentifier,
            groupIdentifier: $groupIdentifier,
            translationSetIdentifier: $translationSetIdentifier,
            slug: $slug,
            language: $language,
            name: $name,
            normalizedName: $normalizedName,
            agencyIdentifier: $agencyIdentifier,
            description: $description,
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
readonly class GroupSnapshotTestData
{
    public function __construct(
        public GroupSnapshotIdentifier  $snapshotIdentifier,
        public GroupIdentifier          $groupIdentifier,
        public TranslationSetIdentifier $translationSetIdentifier,
        public Slug                     $slug,
        public Language                 $language,
        public GroupName                $name,
        public string                   $normalizedName,
        public AgencyIdentifier         $agencyIdentifier,
        public Description              $description,
        public Version                  $version,
        public DateTimeImmutable        $createdAt,
        public PrincipalIdentifier      $editorIdentifier,
        public PrincipalIdentifier      $approverIdentifier,
        public PrincipalIdentifier      $mergerIdentifier,
        public DateTimeImmutable        $mergedAt,
        public PrincipalIdentifier      $sourceEditorIdentifier,
        public DateTimeImmutable        $translatedAt,
        public DateTimeImmutable        $approvedAt,
        public GroupSnapshot            $snapshot,
    ) {
    }
}
