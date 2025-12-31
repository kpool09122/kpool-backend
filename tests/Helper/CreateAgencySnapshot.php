<?php

declare(strict_types=1);

namespace Tests\Helper;

use Illuminate\Support\Facades\DB;

class CreateAgencySnapshot
{
    /**
     * @param array{
     *     agency_id?: string,
     *     translation_set_identifier?: string,
     *     language?: string,
     *     name?: string,
     *     normalized_name?: string,
     *     CEO?: string,
     *     normalized_CEO?: string,
     *     founded_in?: ?string,
     *     description?: string,
     *     version?: int,
     *     created_at?: string
     * } $overrides
     */
    public static function create(string $snapshotId, array $overrides = []): void
    {
        DB::table('agency_snapshots')->insert([
            'id' => $snapshotId,
            'agency_id' => $overrides['agency_id'] ?? StrTestHelper::generateUuid(),
            'translation_set_identifier' => $overrides['translation_set_identifier'] ?? StrTestHelper::generateUuid(),
            'language' => $overrides['language'] ?? 'ko',
            'name' => $overrides['name'] ?? 'JYP엔터테인먼트',
            'normalized_name' => $overrides['normalized_name'] ?? 'jypㅇㅌㅌㅇㅁㅌ',
            'CEO' => $overrides['CEO'] ?? 'J.Y. Park',
            'normalized_CEO' => $overrides['normalized_CEO'] ?? 'j.y. park',
            'founded_in' => $overrides['founded_in'] ?? '1997-04-25',
            'description' => $overrides['description'] ?? 'JYP Entertainment is a South Korean entertainment company.',
            'version' => $overrides['version'] ?? 1,
            'created_at' => $overrides['created_at'] ?? '2024-01-01 00:00:00',
        ]);
    }
}
