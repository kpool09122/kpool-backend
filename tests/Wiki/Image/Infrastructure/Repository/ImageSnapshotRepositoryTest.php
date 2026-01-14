<?php

declare(strict_types=1);

namespace Tests\Wiki\Image\Infrastructure\Repository;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Wiki\Image\Domain\Entity\ImageSnapshot;
use Source\Wiki\Image\Domain\Repository\ImageSnapshotRepositoryInterface;
use Source\Wiki\Image\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Image\Domain\ValueObject\ImageSnapshotIdentifier;
use Source\Wiki\Image\Domain\ValueObject\ImageUsage;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Tests\Helper\CreateImageSnapshot;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ImageSnapshotRepositoryTest extends TestCase
{
    /**
     * 正常系：スナップショットを保存できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSave(): void
    {
        $snapshotId = StrTestHelper::generateUuid();
        $imageId = StrTestHelper::generateUuid();
        $resourceSnapshotId = StrTestHelper::generateUuid();
        $imagePath = '/images/talents/profile-snapshot.jpg';
        $imageUsage = ImageUsage::PROFILE;
        $displayOrder = 1;
        $createdAt = new DateTimeImmutable('2024-01-01 00:00:00');

        $snapshot = new ImageSnapshot(
            new ImageSnapshotIdentifier($snapshotId),
            new ImageIdentifier($imageId),
            new ResourceIdentifier($resourceSnapshotId),
            new ImagePath($imagePath),
            $imageUsage,
            $displayOrder,
            $createdAt,
        );

        $repository = $this->app->make(ImageSnapshotRepositoryInterface::class);
        $repository->save($snapshot);

        $this->assertDatabaseHas('wiki_image_snapshots', [
            'id' => $snapshotId,
            'image_id' => $imageId,
            'resource_snapshot_identifier' => $resourceSnapshotId,
            'image_path' => $imagePath,
            'image_usage' => $imageUsage->value,
            'display_order' => $displayOrder,
        ]);
    }

    /**
     * 正常系：指定したIDのスナップショットが取得できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindById(): void
    {
        $snapshotId = StrTestHelper::generateUuid();
        $imageId = StrTestHelper::generateUuid();
        $resourceSnapshotId = StrTestHelper::generateUuid();

        CreateImageSnapshot::create($snapshotId, [
            'image_id' => $imageId,
            'resource_snapshot_identifier' => $resourceSnapshotId,
            'image_path' => '/images/talents/profile.jpg',
            'image_usage' => ImageUsage::PROFILE->value,
            'display_order' => 1,
            'created_at' => '2024-01-01 00:00:00',
        ]);

        $repository = $this->app->make(ImageSnapshotRepositoryInterface::class);
        $snapshot = $repository->findById(new ImageSnapshotIdentifier($snapshotId));

        $this->assertInstanceOf(ImageSnapshot::class, $snapshot);
        $this->assertSame($snapshotId, (string) $snapshot->snapshotIdentifier());
        $this->assertSame($imageId, (string) $snapshot->imageIdentifier());
        $this->assertSame($resourceSnapshotId, (string) $snapshot->resourceSnapshotIdentifier());
        $this->assertSame('/images/talents/profile.jpg', (string) $snapshot->imagePath());
        $this->assertSame(ImageUsage::PROFILE, $snapshot->imageUsage());
        $this->assertSame(1, $snapshot->displayOrder());
        $this->assertInstanceOf(DateTimeImmutable::class, $snapshot->createdAt());
    }

    /**
     * 正常系：指定したIDのスナップショットが存在しない場合、nullが返却されること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdWhenNotExist(): void
    {
        $repository = $this->app->make(ImageSnapshotRepositoryInterface::class);
        $snapshot = $repository->findById(new ImageSnapshotIdentifier(StrTestHelper::generateUuid()));

        $this->assertNull($snapshot);
    }

    /**
     * 正常系：指定したリソーススナップショットIDに紐づくスナップショット一覧が取得できること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByResourceSnapshot(): void
    {
        $resourceSnapshotId = StrTestHelper::generateUuid();

        $snapshotId1 = StrTestHelper::generateUuid();
        $snapshotId2 = StrTestHelper::generateUuid();
        $otherSnapshotId = StrTestHelper::generateUuid();

        CreateImageSnapshot::create($snapshotId1, [
            'resource_snapshot_identifier' => $resourceSnapshotId,
            'image_path' => '/images/talents/profile.jpg',
            'image_usage' => ImageUsage::PROFILE->value,
            'display_order' => 1,
        ]);

        CreateImageSnapshot::create($snapshotId2, [
            'resource_snapshot_identifier' => $resourceSnapshotId,
            'image_path' => '/images/talents/additional.jpg',
            'image_usage' => ImageUsage::ADDITIONAL->value,
            'display_order' => 2,
        ]);

        CreateImageSnapshot::create($otherSnapshotId, [
            'resource_snapshot_identifier' => StrTestHelper::generateUuid(),
            'image_path' => '/images/groups/cover.jpg',
            'image_usage' => ImageUsage::COVER->value,
            'display_order' => 1,
        ]);

        $repository = $this->app->make(ImageSnapshotRepositoryInterface::class);
        $snapshots = $repository->findByResourceSnapshot(new ResourceIdentifier($resourceSnapshotId));

        $this->assertCount(2, $snapshots);
        $snapshotIds = array_map(
            static fn (ImageSnapshot $snapshot): string => (string) $snapshot->snapshotIdentifier(),
            $snapshots,
        );
        $this->assertContains($snapshotId1, $snapshotIds);
        $this->assertContains($snapshotId2, $snapshotIds);
        $this->assertNotContains($otherSnapshotId, $snapshotIds);

        // display_orderでソートされていることを確認
        $this->assertSame($snapshotId1, (string) $snapshots[0]->snapshotIdentifier());
        $this->assertSame($snapshotId2, (string) $snapshots[1]->snapshotIdentifier());
    }

    /**
     * 正常系：指定したリソーススナップショットIDに紐づくスナップショットが存在しない場合、空配列が返却されること.
     *
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByResourceSnapshotWhenNotExist(): void
    {
        $repository = $this->app->make(ImageSnapshotRepositoryInterface::class);
        $snapshots = $repository->findByResourceSnapshot(
            new ResourceIdentifier(StrTestHelper::generateUuid()),
        );

        $this->assertIsArray($snapshots);
        $this->assertEmpty($snapshots);
    }
}
