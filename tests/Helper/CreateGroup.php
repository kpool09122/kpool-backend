<?php

declare(strict_types=1);

namespace Tests\Helper;

use Illuminate\Support\Facades\DB;
use JsonException;

class CreateGroup
{
    /**
     * @param array{
     *     translation_set_identifier?: string,
     *     translation?: string,
     *     name?: string,
     *     normalized_name?: string,
     *     agency_id?: ?string,
     *     description?: string,
     *     song_identifiers?: array<string>,
     *     image_path?: ?string,
     *     version?: int
     * } $overrides
     * @throws JsonException
     */
    public static function create(string $groupId, array $overrides = []): void
    {
        DB::table('groups')->insert([
            'id' => $groupId,
            'translation_set_identifier' => $overrides['translation_set_identifier'] ?? StrTestHelper::generateUlid(),
            'translation' => $overrides['translation'] ?? 'ja',
            'name' => $overrides['name'] ?? 'テストグループ',
            'normalized_name' => $overrides['normalized_name'] ?? 'テストグループ',
            'agency_id' => $overrides['agency_id'] ?? null,
            'description' => $overrides['description'] ?? '',
            'song_identifiers' => json_encode($overrides['song_identifiers'] ?? [], JSON_THROW_ON_ERROR),
            'image_path' => $overrides['image_path'] ?? null,
            'version' => $overrides['version'] ?? 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
