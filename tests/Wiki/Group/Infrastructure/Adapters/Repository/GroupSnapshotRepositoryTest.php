<?php

declare(strict_types=1);

namespace Tests\Wiki\Group\Infrastructure\Adapters\Repository;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Group\Domain\Entity\GroupSnapshot;
use Source\Wiki\Group\Domain\Repository\GroupSnapshotRepositoryInterface;
use Source\Wiki\Group\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Group\Domain\ValueObject\Description;
use Source\Wiki\Group\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Group\Domain\ValueObject\GroupName;
use Source\Wiki\Group\Domain\ValueObject\GroupSnapshotIdentifier;
use Source\Wiki\Group\Domain\ValueObject\SongIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Tests\Helper\CreateGroupSnapshot;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class GroupSnapshotRepositoryTest extends TestCase
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
        $snapshotId = StrTestHelper::generateUlid();
        $groupId = StrTestHelper::generateUlid();
        $translationSetIdentifier = StrTestHelper::generateUlid();
        $language = Language::KOREAN;
        $name = 'TWICE';
        $normalizedName = 'twice';
        $agencyId = StrTestHelper::generateUlid();
        $description = 'TWICE description';
        $songIdentifiers = [
            new SongIdentifier(StrTestHelper::generateUlid()),
            new SongIdentifier(StrTestHelper::generateUlid()),
        ];
        $imagePath = '/resources/public/images/twice.webp';
        $version = 1;
        $createdAt = new DateTimeImmutable('2024-01-01 00:00:00');

        $snapshot = new GroupSnapshot(
            new GroupSnapshotIdentifier($snapshotId),
            new GroupIdentifier($groupId),
            new TranslationSetIdentifier($translationSetIdentifier),
            $language,
            new GroupName($name),
            $normalizedName,
            new AgencyIdentifier($agencyId),
            new Description($description),
            $songIdentifiers,
            new ImagePath($imagePath),
            new Version($version),
            $createdAt,
        );

        $repository = $this->app->make(GroupSnapshotRepositoryInterface::class);
        $repository->save($snapshot);

        $this->assertDatabaseHas('group_snapshots', [
            'id' => $snapshotId,
            'group_id' => $groupId,
            'translation_set_identifier' => $translationSetIdentifier,
            'translation' => $language->value,
            'name' => $name,
            'normalized_name' => $normalizedName,
            'agency_id' => $agencyId,
            'description' => $description,
            'image_path' => $imagePath,
            'version' => $version,
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
        $snapshotId = StrTestHelper::generateUlid();
        $groupId = StrTestHelper::generateUlid();

        $snapshot = new GroupSnapshot(
            new GroupSnapshotIdentifier($snapshotId),
            new GroupIdentifier($groupId),
            new TranslationSetIdentifier(StrTestHelper::generateUlid()),
            Language::KOREAN,
            new GroupName('TWICE'),
            'twice',
            null,
            new Description('TWICE is a South Korean girl group.'),
            [],
            null,
            new Version(1),
            new DateTimeImmutable('2024-01-01 00:00:00'),
        );

        $repository = $this->app->make(GroupSnapshotRepositoryInterface::class);
        $repository->save($snapshot);

        $this->assertDatabaseHas('group_snapshots', [
            'id' => $snapshotId,
            'group_id' => $groupId,
            'agency_id' => null,
            'image_path' => null,
        ]);
    }

    /**
     * 正常系：指定したGroupIDのスナップショット一覧が取得できること
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByGroupIdentifier(): void
    {
        $groupId = StrTestHelper::generateUlid();
        $translationSetIdentifier = StrTestHelper::generateUlid();

        // バージョン1のスナップショット
        $snapshotId1 = StrTestHelper::generateUlid();
        CreateGroupSnapshot::create($snapshotId1, [
            'group_id' => $groupId,
            'translation_set_identifier' => $translationSetIdentifier,
            'name' => 'TWICE v1',
            'normalized_name' => 'twice v1',
            'description' => 'Description v1',
            'version' => 1,
            'created_at' => '2024-01-01 00:00:00',
        ]);

        // バージョン2のスナップショット
        $snapshotId2 = StrTestHelper::generateUlid();
        CreateGroupSnapshot::create($snapshotId2, [
            'group_id' => $groupId,
            'translation_set_identifier' => $translationSetIdentifier,
            'name' => 'TWICE v2',
            'normalized_name' => 'twice v2',
            'description' => 'Description v2',
            'version' => 2,
            'created_at' => '2024-01-02 00:00:00',
        ]);

        // 別のGroupのスナップショット（取得されないはず）
        $otherGroupId = StrTestHelper::generateUlid();
        $snapshotId3 = StrTestHelper::generateUlid();
        CreateGroupSnapshot::create($snapshotId3, [
            'group_id' => $otherGroupId,
            'name' => 'aespa',
            'normalized_name' => 'aespa',
            'description' => 'aespa is a South Korean girl group.',
        ]);

        $repository = $this->app->make(GroupSnapshotRepositoryInterface::class);
        $snapshots = $repository->findByGroupIdentifier(new GroupIdentifier($groupId));

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
    public function testFindByGroupIdentifierWhenNoSnapshots(): void
    {
        $repository = $this->app->make(GroupSnapshotRepositoryInterface::class);
        $snapshots = $repository->findByGroupIdentifier(
            new GroupIdentifier(StrTestHelper::generateUlid())
        );

        $this->assertIsArray($snapshots);
        $this->assertEmpty($snapshots);
    }

    /**
     * 正常系：指定したGroupIDとバージョンのスナップショットが取得できること
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByGroupAndVersion(): void
    {
        $groupId = StrTestHelper::generateUlid();
        $snapshotId = StrTestHelper::generateUlid();
        $translationSetIdentifier = StrTestHelper::generateUlid();
        $name = 'TWICE';
        $normalizedName = 'twice';
        $agencyId = StrTestHelper::generateUlid();
        $description = 'TWICE is a South Korean girl group.';
        $imagePath = '/resources/public/images/twice.webp';
        $version = 3;

        CreateGroupSnapshot::create($snapshotId, [
            'group_id' => $groupId,
            'translation_set_identifier' => $translationSetIdentifier,
            'name' => $name,
            'normalized_name' => $normalizedName,
            'agency_id' => $agencyId,
            'description' => $description,
            'image_path' => $imagePath,
            'version' => $version,
        ]);

        $repository = $this->app->make(GroupSnapshotRepositoryInterface::class);
        $snapshot = $repository->findByGroupAndVersion(
            new GroupIdentifier($groupId),
            new Version($version)
        );

        $this->assertNotNull($snapshot);
        $this->assertSame($snapshotId, (string)$snapshot->snapshotIdentifier());
        $this->assertSame($groupId, (string)$snapshot->groupIdentifier());
        $this->assertSame($translationSetIdentifier, (string)$snapshot->translationSetIdentifier());
        $this->assertSame(Language::KOREAN, $snapshot->language());
        $this->assertSame($name, (string)$snapshot->name());
        $this->assertSame($normalizedName, $snapshot->normalizedName());
        $this->assertSame($agencyId, (string)$snapshot->agencyIdentifier());
        $this->assertSame($description, (string)$snapshot->description());
        $this->assertSame($imagePath, (string)$snapshot->imagePath());
        $this->assertSame($version, $snapshot->version()->value());
    }

    /**
     * 正常系：該当するスナップショットが存在しない場合、nullが返却されること
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByGroupAndVersionWhenNoSnapshot(): void
    {
        $repository = $this->app->make(GroupSnapshotRepositoryInterface::class);
        $snapshot = $repository->findByGroupAndVersion(
            new GroupIdentifier(StrTestHelper::generateUlid()),
            new Version(1)
        );

        $this->assertNull($snapshot);
    }
}
