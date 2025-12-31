<?php

declare(strict_types=1);

namespace Tests\Helper;

use Illuminate\Support\Facades\DB;
use JsonException;

class CreateSong
{
    /**
     * @param array{
     *     translation_set_identifier?: string,
     *     language?: string,
     *     name?: string,
     *     agency_id?: ?string,
     *     belong_identifiers?: array<string>,
     *     lyricist?: string,
     *     composer?: string,
     *     release_date?: ?string,
     *     lyrics?: string,
     *     overview?: string,
     *     cover_image_path?: ?string,
     *     music_video_link?: ?string,
     *     version?: int
     * } $overrides
     * @throws JsonException
     */
    public static function create(string $songId, array $overrides = []): void
    {
        DB::table('songs')->insert([
            'id' => $songId,
            'translation_set_identifier' => $overrides['translation_set_identifier'] ?? StrTestHelper::generateUuid(),
            'language' => $overrides['language'] ?? 'ko',
            'name' => $overrides['name'] ?? 'LALALALA',
            'agency_id' => $overrides['agency_id'] ?? null,
            'belong_identifiers' => json_encode($overrides['belong_identifiers'] ?? [], JSON_THROW_ON_ERROR),
            'lyricist' => $overrides['lyricist'] ?? 'Bang Chan, Changbin, Han',
            'composer' => $overrides['composer'] ?? 'Bang Chan, Changbin, Han',
            'release_date' => $overrides['release_date'] ?? null,
            'lyrics' => $overrides['lyrics'] ?? '',
            'overview' => $overrides['overview'] ?? 'Stray Kids樂-STAR title track.',
            'cover_image_path' => $overrides['cover_image_path'] ?? null,
            'music_video_link' => $overrides['music_video_link'] ?? null,
            'version' => $overrides['version'] ?? 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
