<?php

declare(strict_types=1);

namespace Tests\Wiki\Agency\Infrastructure\Adapters\Repository;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Agency\Domain\Entity\AgencySnapshot;
use Source\Wiki\Agency\Domain\Repository\AgencySnapshotRepositoryInterface;
use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Agency\Domain\ValueObject\AgencyName;
use Source\Wiki\Agency\Domain\ValueObject\AgencySnapshotIdentifier;
use Source\Wiki\Agency\Domain\ValueObject\CEO;
use Source\Wiki\Agency\Domain\ValueObject\Description;
use Source\Wiki\Agency\Domain\ValueObject\FoundedIn;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Tests\Helper\CreateAgencySnapshot;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class AgencySnapshotRepositoryTest extends TestCase
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
        $agencyId = StrTestHelper::generateUuid();
        $translationSetIdentifier = StrTestHelper::generateUuid();
        $language = Language::KOREAN;
        $name = 'JYP엔터테인먼트';
        $normalizedName = 'jypㅇㅌㅌㅇㅁㅌ';
        $CEO = 'J.Y. Park';
        $normalizedCEO = 'j.y. park';
        $foundedIn = new DateTimeImmutable('1997-04-25');
        $description = 'JYP Entertainment description';
        $version = 1;
        $createdAt = new DateTimeImmutable('2024-01-01 00:00:00');

        $snapshot = new AgencySnapshot(
            new AgencySnapshotIdentifier($snapshotId),
            new AgencyIdentifier($agencyId),
            new TranslationSetIdentifier($translationSetIdentifier),
            $language,
            new AgencyName($name),
            $normalizedName,
            new CEO($CEO),
            $normalizedCEO,
            new FoundedIn($foundedIn),
            new Description($description),
            new Version($version),
            $createdAt,
        );

        $repository = $this->app->make(AgencySnapshotRepositoryInterface::class);
        $repository->save($snapshot);

        $this->assertDatabaseHas('agency_snapshots', [
            'id' => $snapshotId,
            'agency_id' => $agencyId,
            'translation_set_identifier' => $translationSetIdentifier,
            'language' => $language->value,
            'name' => $name,
            'normalized_name' => $normalizedName,
            'CEO' => $CEO,
            'normalized_CEO' => $normalizedCEO,
            'founded_in' => $foundedIn->format('Y-m-d'),
            'description' => $description,
            'version' => $version,
        ]);
    }

    /**
     * 正常系：foundedInがnullでもスナップショットを保存できること
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveWithNullFoundedIn(): void
    {
        $snapshotId = StrTestHelper::generateUuid();
        $agencyId = StrTestHelper::generateUuid();

        $snapshot = new AgencySnapshot(
            new AgencySnapshotIdentifier($snapshotId),
            new AgencyIdentifier($agencyId),
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            Language::KOREAN,
            new AgencyName('SM엔터테인먼트'),
            'smㅇㅌㅌㅇㅁㅌ',
            new CEO('Lee Sung-su'),
            'lee sung-su',
            null,
            new Description('SM Entertainment is a South Korean entertainment company.'),
            new Version(1),
            new DateTimeImmutable('2024-01-01 00:00:00'),
        );

        $repository = $this->app->make(AgencySnapshotRepositoryInterface::class);
        $repository->save($snapshot);

        $this->assertDatabaseHas('agency_snapshots', [
            'id' => $snapshotId,
            'agency_id' => $agencyId,
            'founded_in' => null,
        ]);
    }

    /**
     * 正常系：指定したAgencyIDのスナップショット一覧が取得できること
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByAgencyIdentifier(): void
    {
        $agencyId = StrTestHelper::generateUuid();
        $translationSetIdentifier = StrTestHelper::generateUuid();

        // バージョン1のスナップショット
        $snapshotId1 = StrTestHelper::generateUuid();
        CreateAgencySnapshot::create($snapshotId1, [
            'agency_id' => $agencyId,
            'translation_set_identifier' => $translationSetIdentifier,
            'name' => 'JYP v1',
            'normalized_name' => 'jyp v1',
            'CEO' => 'CEO v1',
            'normalized_CEO' => 'ceo v1',
            'description' => 'Description v1',
            'version' => 1,
            'created_at' => '2024-01-01 00:00:00',
        ]);

        // バージョン2のスナップショット
        $snapshotId2 = StrTestHelper::generateUuid();
        CreateAgencySnapshot::create($snapshotId2, [
            'agency_id' => $agencyId,
            'translation_set_identifier' => $translationSetIdentifier,
            'name' => 'JYP v2',
            'normalized_name' => 'jyp v2',
            'CEO' => 'CEO v2',
            'normalized_CEO' => 'ceo v2',
            'description' => 'Description v2',
            'version' => 2,
            'created_at' => '2024-01-02 00:00:00',
        ]);

        // 別のAgencyのスナップショット（取得されないはず）
        $otherAgencyId = StrTestHelper::generateUuid();
        $snapshotId3 = StrTestHelper::generateUuid();
        CreateAgencySnapshot::create($snapshotId3, [
            'agency_id' => $otherAgencyId,
            'name' => 'SM엔터테인먼트',
            'normalized_name' => 'smㅇㅌㅌㅇㅁㅌ',
            'CEO' => 'Lee Sung-su',
            'normalized_CEO' => 'lee sung-su',
            'founded_in' => null,
            'description' => 'SM Entertainment is a South Korean entertainment company.',
        ]);

        $repository = $this->app->make(AgencySnapshotRepositoryInterface::class);
        $snapshots = $repository->findByAgencyIdentifier(new AgencyIdentifier($agencyId));

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
    public function testFindByAgencyIdentifierWhenNoSnapshots(): void
    {
        $repository = $this->app->make(AgencySnapshotRepositoryInterface::class);
        $snapshots = $repository->findByAgencyIdentifier(
            new AgencyIdentifier(StrTestHelper::generateUuid())
        );

        $this->assertIsArray($snapshots);
        $this->assertEmpty($snapshots);
    }

    /**
     * 正常系：指定したAgencyIDとバージョンのスナップショットが取得できること
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByAgencyAndVersion(): void
    {
        $agencyId = StrTestHelper::generateUuid();
        $snapshotId = StrTestHelper::generateUuid();
        $translationSetIdentifier = StrTestHelper::generateUuid();
        $name = 'JYP엔터테인먼트';
        $normalizedName = 'jypㅇㅌㅌㅇㅁㅌ';
        $CEO = 'J.Y. Park';
        $normalizedCEO = 'j.y. park';
        $foundedIn = '1997-04-25';
        $description = 'JYP Entertainment is a South Korean entertainment company.';
        $version = 3;

        CreateAgencySnapshot::create($snapshotId, [
            'agency_id' => $agencyId,
            'translation_set_identifier' => $translationSetIdentifier,
            'name' => $name,
            'normalized_name' => $normalizedName,
            'CEO' => $CEO,
            'normalized_CEO' => $normalizedCEO,
            'founded_in' => $foundedIn,
            'description' => $description,
            'version' => $version,
        ]);

        $repository = $this->app->make(AgencySnapshotRepositoryInterface::class);
        $snapshot = $repository->findByAgencyAndVersion(
            new AgencyIdentifier($agencyId),
            new Version($version)
        );

        $this->assertNotNull($snapshot);
        $this->assertSame($snapshotId, (string)$snapshot->snapshotIdentifier());
        $this->assertSame($agencyId, (string)$snapshot->agencyIdentifier());
        $this->assertSame($translationSetIdentifier, (string)$snapshot->translationSetIdentifier());
        $this->assertSame(Language::KOREAN, $snapshot->language());
        $this->assertSame($name, (string)$snapshot->name());
        $this->assertSame($normalizedName, $snapshot->normalizedName());
        $this->assertSame($CEO, (string)$snapshot->CEO());
        $this->assertSame($normalizedCEO, $snapshot->normalizedCEO());
        $this->assertSame($foundedIn, $snapshot->foundedIn()->value()->format('Y-m-d'));
        $this->assertSame($description, (string)$snapshot->description());
        $this->assertSame($version, $snapshot->version()->value());
    }

    /**
     * 正常系：該当するスナップショットが存在しない場合、nullが返却されること
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByAgencyAndVersionWhenNoSnapshot(): void
    {
        $repository = $this->app->make(AgencySnapshotRepositoryInterface::class);
        $snapshot = $repository->findByAgencyAndVersion(
            new AgencyIdentifier(StrTestHelper::generateUuid()),
            new Version(1)
        );

        $this->assertNull($snapshot);
    }

    /**
     * 正常系：翻訳セットIDとバージョンで複数のSnapshotを取得できること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByTranslationSetIdentifierAndVersion(): void
    {
        $translationSetIdentifier = StrTestHelper::generateUuid();
        $agencyIdKo = StrTestHelper::generateUuid();
        $agencyIdJa = StrTestHelper::generateUuid();
        $version = 2;

        // 韓国語版Snapshotバージョン2
        $snapshotIdKo = StrTestHelper::generateUuid();
        CreateAgencySnapshot::create($snapshotIdKo, [
            'agency_id' => $agencyIdKo,
            'translation_set_identifier' => $translationSetIdentifier,
            'language' => Language::KOREAN->value,
            'name' => 'JYP엔터테인먼트 v2',
            'normalized_name' => 'jypㅇㅌㅌㅇㅁㅌ v2',
            'CEO' => 'J.Y. Park',
            'normalized_CEO' => 'j.y. park',
            'founded_in' => '1997-04-25',
            'description' => 'Korean description v2',
            'version' => $version,
        ]);

        // 日本語版Snapshotバージョン2
        $snapshotIdJa = StrTestHelper::generateUuid();
        CreateAgencySnapshot::create($snapshotIdJa, [
            'agency_id' => $agencyIdJa,
            'translation_set_identifier' => $translationSetIdentifier,
            'language' => Language::JAPANESE->value,
            'name' => 'JYPエンターテインメント v2',
            'normalized_name' => 'jypえんたーていんめんと v2',
            'CEO' => 'J.Y. パク',
            'normalized_CEO' => 'j.y. ぱく',
            'founded_in' => '1997-04-25',
            'description' => 'Japanese description v2',
            'version' => $version,
        ]);

        // 同じ翻訳セットだが異なるバージョン（取得されないはず）
        $snapshotIdV1 = StrTestHelper::generateUuid();
        CreateAgencySnapshot::create($snapshotIdV1, [
            'agency_id' => $agencyIdKo,
            'translation_set_identifier' => $translationSetIdentifier,
            'language' => Language::KOREAN->value,
            'name' => 'JYP엔터테인먼트 v1',
            'normalized_name' => 'jypㅇㅌㅌㅇㅁㅌ v1',
            'CEO' => 'J.Y. Park',
            'normalized_CEO' => 'j.y. park',
            'founded_in' => '1997-04-25',
            'description' => 'Korean description v1',
            'version' => 1,
        ]);

        // 別の翻訳セットのSnapshot（取得されないはず）
        $otherTranslationSetIdentifier = StrTestHelper::generateUuid();
        $snapshotIdOther = StrTestHelper::generateUuid();
        CreateAgencySnapshot::create($snapshotIdOther, [
            'agency_id' => StrTestHelper::generateUuid(),
            'translation_set_identifier' => $otherTranslationSetIdentifier,
            'language' => Language::KOREAN->value,
            'name' => 'SM엔터테인먼트',
            'normalized_name' => 'smㅇㅌㅌㅇㅁㅌ',
            'CEO' => 'Lee Sung-su',
            'normalized_CEO' => 'lee sung-su',
            'founded_in' => '1995-02-14',
            'description' => 'SM description',
            'version' => $version,
        ]);

        $repository = $this->app->make(AgencySnapshotRepositoryInterface::class);
        $snapshots = $repository->findByTranslationSetIdentifierAndVersion(
            new TranslationSetIdentifier($translationSetIdentifier),
            new Version($version)
        );

        $this->assertCount(2, $snapshots);

        $snapshotIds = array_map(fn (AgencySnapshot $s) => (string) $s->snapshotIdentifier(), $snapshots);
        $this->assertContains($snapshotIdKo, $snapshotIds);
        $this->assertContains($snapshotIdJa, $snapshotIds);
        $this->assertNotContains($snapshotIdV1, $snapshotIds);
        $this->assertNotContains($snapshotIdOther, $snapshotIds);
    }

    /**
     * 正常系：該当するSnapshotが存在しない場合、空の配列が返却されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByTranslationSetIdentifierAndVersionWhenNoSnapshots(): void
    {
        $repository = $this->app->make(AgencySnapshotRepositoryInterface::class);
        $snapshots = $repository->findByTranslationSetIdentifierAndVersion(
            new TranslationSetIdentifier(StrTestHelper::generateUuid()),
            new Version(1)
        );

        $this->assertIsArray($snapshots);
        $this->assertEmpty($snapshots);
    }
}
