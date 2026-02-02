<?php

declare(strict_types=1);

namespace Tests\Wiki\Agency\Domain\Entity;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Agency\Domain\Entity\AgencySnapshot;
use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Agency\Domain\ValueObject\AgencySnapshotIdentifier;
use Source\Wiki\Agency\Domain\ValueObject\Description;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Agency\CEO;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Agency\FoundedIn;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\Name;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class AgencySnapshotTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $data = $this->createDummyAgencySnapshot();
        $snapshot = $data->snapshot;

        $this->assertSame((string)$data->snapshotIdentifier, (string)$snapshot->snapshotIdentifier());
        $this->assertSame((string)$data->agencyIdentifier, (string)$snapshot->agencyIdentifier());
        $this->assertSame((string)$data->translationSetIdentifier, (string)$snapshot->translationSetIdentifier());
        $this->assertSame($data->language->value, $snapshot->language()->value);
        $this->assertSame((string)$data->name, (string)$snapshot->name());
        $this->assertSame($data->normalizedName, $snapshot->normalizedName());
        $this->assertSame((string)$data->CEO, (string)$snapshot->CEO());
        $this->assertSame($data->normalizedCEO, $snapshot->normalizedCEO());
        $this->assertSame($data->foundedIn->value(), $snapshot->foundedIn()->value());
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
     * 正常系: foundedInがnullでもインスタンスが生成されること
     *
     * @return void
     */
    public function test__constructWithNullFoundedIn(): void
    {
        $snapshotIdentifier = new AgencySnapshotIdentifier(StrTestHelper::generateUuid());
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUuid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $language = Language::KOREAN;
        $name = new Name('SM엔터테인먼트');
        $normalizedName = 'smㅇㅌㅌㅇㅁㅌ';
        $CEO = new CEO('Lee Sung-su');
        $normalizedCEO = 'lee sung-su';
        $description = new Description('SM Entertainment is a South Korean entertainment company.');
        $version = new Version(1);
        $createdAt = new DateTimeImmutable('2024-01-01 00:00:00');

        $snapshot = new AgencySnapshot(
            $snapshotIdentifier,
            $agencyIdentifier,
            $translationSetIdentifier,
            new Slug('sm-entertainment'),
            $language,
            $name,
            $normalizedName,
            $CEO,
            $normalizedCEO,
            null,
            $description,
            $version,
            $createdAt,
        );

        $this->assertNull($snapshot->foundedIn());
    }

    /**
     * 正常系: オプショナルなプロパティがnullでもインスタンスが生成されること
     *
     * @return void
     */
    public function test__constructWithNullOptionalProperties(): void
    {
        $snapshotIdentifier = new AgencySnapshotIdentifier(StrTestHelper::generateUuid());
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUuid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $language = Language::KOREAN;
        $name = new Name('SM엔터테인먼트');
        $normalizedName = 'smㅇㅌㅌㅇㅁㅌ';
        $CEO = new CEO('Lee Sung-su');
        $normalizedCEO = 'lee sung-su';
        $foundedIn = new FoundedIn(new DateTimeImmutable('1995-02-14'));
        $description = new Description('SM Entertainment is a South Korean entertainment company.');
        $version = new Version(1);
        $createdAt = new DateTimeImmutable('2024-01-01 00:00:00');

        $snapshot = new AgencySnapshot(
            $snapshotIdentifier,
            $agencyIdentifier,
            $translationSetIdentifier,
            new Slug('sm-entertainment'),
            $language,
            $name,
            $normalizedName,
            $CEO,
            $normalizedCEO,
            $foundedIn,
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
     * ダミーのAgencySnapshotを作成するヘルパーメソッド
     *
     * @return AgencySnapshotTestData
     */
    private function createDummyAgencySnapshot(): AgencySnapshotTestData
    {
        $snapshotIdentifier = new AgencySnapshotIdentifier(StrTestHelper::generateUuid());
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUuid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUuid());
        $slug = new Slug('jyp-entertainment');
        $language = Language::KOREAN;
        $name = new Name('JYP엔터테인먼트');
        $normalizedName = 'jypㅇㅌㅌㅇㅁㅌ';
        $CEO = new CEO('J.Y. Park');
        $normalizedCEO = 'j.y. park';
        $foundedIn = new FoundedIn(new DateTimeImmutable('1997-04-25'));
        $description = new Description('JYP Entertainment is a South Korean entertainment company.');
        $version = new Version(1);
        $createdAt = new DateTimeImmutable('2024-01-01 00:00:00');
        $editorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $approverIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $mergerIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $mergedAt = new DateTimeImmutable('2024-01-02 00:00:00');
        $sourceEditorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $translatedAt = new DateTimeImmutable('2024-01-03 00:00:00');
        $approvedAt = new DateTimeImmutable('2024-01-04 00:00:00');

        $snapshot = new AgencySnapshot(
            $snapshotIdentifier,
            $agencyIdentifier,
            $translationSetIdentifier,
            $slug,
            $language,
            $name,
            $normalizedName,
            $CEO,
            $normalizedCEO,
            $foundedIn,
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

        return new AgencySnapshotTestData(
            snapshotIdentifier: $snapshotIdentifier,
            agencyIdentifier: $agencyIdentifier,
            translationSetIdentifier: $translationSetIdentifier,
            slug: $slug,
            language: $language,
            name: $name,
            normalizedName: $normalizedName,
            CEO: $CEO,
            normalizedCEO: $normalizedCEO,
            foundedIn: $foundedIn,
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
readonly class AgencySnapshotTestData
{
    public function __construct(
        public AgencySnapshotIdentifier $snapshotIdentifier,
        public AgencyIdentifier         $agencyIdentifier,
        public TranslationSetIdentifier $translationSetIdentifier,
        public Slug                     $slug,
        public Language                 $language,
        public Name                     $name,
        public string                   $normalizedName,
        public CEO                      $CEO,
        public string                   $normalizedCEO,
        public FoundedIn                $foundedIn,
        public Description              $description,
        public Version                  $version,
        public DateTimeImmutable        $createdAt,
        public PrincipalIdentifier      $editorIdentifier,
        public PrincipalIdentifier      $approverIdentifier,
        public PrincipalIdentifier      $mergerIdentifier,
        public DateTimeImmutable         $mergedAt,
        public PrincipalIdentifier       $sourceEditorIdentifier,
        public DateTimeImmutable         $translatedAt,
        public DateTimeImmutable         $approvedAt,
        public AgencySnapshot            $snapshot,
    ) {
    }
}
