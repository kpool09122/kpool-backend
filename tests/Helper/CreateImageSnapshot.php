<?php

declare(strict_types=1);

namespace Tests\Helper;

use Illuminate\Support\Facades\DB;
use Source\Wiki\Image\Domain\ValueObject\ImageUsage;

class CreateImageSnapshot
{
    /**
     * @param array{
     *     image_id?: string,
     *     resource_snapshot_identifier?: string,
     *     image_path?: string,
     *     image_usage?: string,
     *     display_order?: int,
     *     created_at?: string,
     * } $overrides
     */
    public static function create(string $snapshotId, array $overrides = []): void
    {
        DB::table('wiki_image_snapshots')->insert([
            'id' => $snapshotId,
            'image_id' => $overrides['image_id'] ?? StrTestHelper::generateUuid(),
            'resource_snapshot_identifier' => $overrides['resource_snapshot_identifier'] ?? StrTestHelper::generateUuid(),
            'image_path' => $overrides['image_path'] ?? '/images/test/snapshot.jpg',
            'image_usage' => $overrides['image_usage'] ?? ImageUsage::PROFILE->value,
            'display_order' => $overrides['display_order'] ?? 1,
            'created_at' => $overrides['created_at'] ?? '2024-01-01 00:00:00',
        ]);
    }
}
