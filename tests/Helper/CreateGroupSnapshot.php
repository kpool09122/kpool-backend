<?php

declare(strict_types=1);

namespace Tests\Helper;

use Illuminate\Support\Facades\DB;

class CreateGroupSnapshot
{
    /**
     * @param array{
     *     group_id?: string,
     *     translation_set_identifier?: string,
     *     translation?: string,
     *     name?: string,
     *     normalized_name?: string,
     *     agency_id?: ?string,
     *     description?: string,
     *     song_identifiers?: array<int, string>,
     *     image_path?: ?string,
     *     version?: int,
     *     created_at?: string
     * } $overrides
     */
    public static function create(string $snapshotId, array $overrides = []): void
    {
        DB::table('group_snapshots')->insert([
            'id' => $snapshotId,
            'group_id' => $overrides['group_id'] ?? StrTestHelper::generateUlid(),
            'translation_set_identifier' => $overrides['translation_set_identifier'] ?? StrTestHelper::generateUlid(),
            'translation' => $overrides['translation'] ?? 'ko',
            'name' => $overrides['name'] ?? 'TWICE',
            'normalized_name' => $overrides['normalized_name'] ?? 'twice',
            'agency_id' => $overrides['agency_id'] ?? StrTestHelper::generateUlid(),
            'description' => $overrides['description'] ?? 'TWICE is a South Korean girl group.',
            'song_identifiers' => json_encode($overrides['song_identifiers'] ?? []),
            'image_path' => $overrides['image_path'] ?? '/resources/public/images/twice.webp',
            'version' => $overrides['version'] ?? 1,
            'created_at' => $overrides['created_at'] ?? '2024-01-01 00:00:00',
        ]);
    }
}
