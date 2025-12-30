<?php

declare(strict_types=1);

namespace Tests\Wiki\Agency\Domain\Entity;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Agency\Domain\Entity\AgencySnapshot;
use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Agency\Domain\ValueObject\AgencyName;
use Source\Wiki\Agency\Domain\ValueObject\AgencySnapshotIdentifier;
use Source\Wiki\Agency\Domain\ValueObject\CEO;
use Source\Wiki\Agency\Domain\ValueObject\Description;
use Source\Wiki\Agency\Domain\ValueObject\FoundedIn;
use Source\Wiki\Shared\Domain\ValueObject\Version;
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
    }

    /**
     * 正常系: foundedInがnullでもインスタンスが生成されること
     *
     * @return void
     */
    public function test__constructWithNullFoundedIn(): void
    {
        $snapshotIdentifier = new AgencySnapshotIdentifier(StrTestHelper::generateUlid());
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $language = Language::KOREAN;
        $name = new AgencyName('SM엔터테인먼트');
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
     * ダミーのAgencySnapshotを作成するヘルパーメソッド
     *
     * @return AgencySnapshotTestData
     */
    private function createDummyAgencySnapshot(): AgencySnapshotTestData
    {
        $snapshotIdentifier = new AgencySnapshotIdentifier(StrTestHelper::generateUlid());
        $agencyIdentifier = new AgencyIdentifier(StrTestHelper::generateUlid());
        $translationSetIdentifier = new TranslationSetIdentifier(StrTestHelper::generateUlid());
        $language = Language::KOREAN;
        $name = new AgencyName('JYP엔터테인먼트');
        $normalizedName = 'jypㅇㅌㅌㅇㅁㅌ';
        $CEO = new CEO('J.Y. Park');
        $normalizedCEO = 'j.y. park';
        $foundedIn = new FoundedIn(new DateTimeImmutable('1997-04-25'));
        $description = new Description('JYP Entertainment is a South Korean entertainment company.');
        $version = new Version(1);
        $createdAt = new DateTimeImmutable('2024-01-01 00:00:00');

        $snapshot = new AgencySnapshot(
            $snapshotIdentifier,
            $agencyIdentifier,
            $translationSetIdentifier,
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

        return new AgencySnapshotTestData(
            snapshotIdentifier: $snapshotIdentifier,
            agencyIdentifier: $agencyIdentifier,
            translationSetIdentifier: $translationSetIdentifier,
            language: $language,
            name: $name,
            normalizedName: $normalizedName,
            CEO: $CEO,
            normalizedCEO: $normalizedCEO,
            foundedIn: $foundedIn,
            description: $description,
            version: $version,
            createdAt: $createdAt,
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
        public AgencySnapshotIdentifier  $snapshotIdentifier,
        public AgencyIdentifier          $agencyIdentifier,
        public TranslationSetIdentifier  $translationSetIdentifier,
        public Language                  $language,
        public AgencyName                $name,
        public string                    $normalizedName,
        public CEO                       $CEO,
        public string                    $normalizedCEO,
        public FoundedIn                 $foundedIn,
        public Description               $description,
        public Version                   $version,
        public DateTimeImmutable         $createdAt,
        public AgencySnapshot            $snapshot,
    ) {
    }
}
