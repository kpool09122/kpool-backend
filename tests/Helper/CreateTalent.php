<?php

declare(strict_types=1);

namespace Tests\Helper;

use Illuminate\Support\Facades\DB;
use JsonException;

class CreateTalent
{
    /**
     * @param array{
     *     translation_set_identifier?: string,
     *     language?: string,
     *     name?: string,
     *     real_name?: string,
     *     agency_id?: ?string,
     *     group_identifiers?: array<string>,
     *     birthday?: ?string,
     *     career?: string,
     *     image_link?: ?string,
     *     relevant_video_links?: array<string>,
     *     version?: int
     * } $overrides
     * @throws JsonException
     */
    public static function create(string $talentId, array $overrides = []): void
    {
        DB::table('talents')->insert([
            'id' => $talentId,
            'translation_set_identifier' => $overrides['translation_set_identifier'] ?? StrTestHelper::generateUuid(),
            'language' => $overrides['language'] ?? 'ko',
            'name' => $overrides['name'] ?? '방찬',
            'real_name' => $overrides['real_name'] ?? '크리스토퍼 방',
            'agency_id' => $overrides['agency_id'] ?? null,
            'group_identifiers' => json_encode($overrides['group_identifiers'] ?? [], JSON_THROW_ON_ERROR),
            'birthday' => $overrides['birthday'] ?? null,
            'career' => $overrides['career'] ?? 'Stray Kids leader, producer, and rapper. Member of 3RACHA.',
            'image_link' => $overrides['image_link'] ?? null,
            'relevant_video_links' => json_encode($overrides['relevant_video_links'] ?? [], JSON_THROW_ON_ERROR),
            'version' => $overrides['version'] ?? 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
