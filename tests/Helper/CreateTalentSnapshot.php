<?php

declare(strict_types=1);

namespace Tests\Helper;

use Illuminate\Support\Facades\DB;

class CreateTalentSnapshot
{
    /**
     * @param array{
     *     talent_id?: string,
     *     translation_set_identifier?: string,
     *     language?: string,
     *     name?: string,
     *     real_name?: string,
     *     agency_id?: ?string,
     *     group_identifiers?: array<int, string>,
     *     birthday?: ?string,
     *     career?: string,
     *     image_link?: ?string,
     *     relevant_video_links?: array<int, string>,
     *     version?: int,
     *     created_at?: string
     * } $overrides
     */
    public static function create(string $snapshotId, array $overrides = []): void
    {
        DB::table('talent_snapshots')->insert([
            'id' => $snapshotId,
            'talent_id' => $overrides['talent_id'] ?? StrTestHelper::generateUuid(),
            'translation_set_identifier' => $overrides['translation_set_identifier'] ?? StrTestHelper::generateUuid(),
            'language' => $overrides['language'] ?? 'ko',
            'name' => $overrides['name'] ?? '채영',
            'real_name' => $overrides['real_name'] ?? '손채영',
            'agency_id' => $overrides['agency_id'] ?? StrTestHelper::generateUuid(),
            'birthday' => $overrides['birthday'] ?? '1999-04-23',
            'career' => $overrides['career'] ?? 'TWICE member since 2015.',
            'image_link' => $overrides['image_link'] ?? '/resources/public/images/chaeyoung.webp',
            'relevant_video_links' => json_encode($overrides['relevant_video_links'] ?? []),
            'version' => $overrides['version'] ?? 1,
            'created_at' => $overrides['created_at'] ?? '2024-01-01 00:00:00',
        ]);

        $groupIdentifiers = $overrides['group_identifiers'] ?? [];
        foreach ($groupIdentifiers as $groupId) {
            DB::table('talent_snapshot_group')->insert([
                'talent_snapshot_id' => $snapshotId,
                'group_id' => $groupId,
            ]);
        }
    }
}
