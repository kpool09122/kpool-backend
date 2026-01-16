<?php

declare(strict_types=1);

namespace Tests\Helper;

use Illuminate\Support\Facades\DB;

class CreateSong
{
    /**
     * @param array{
     *     translation_set_identifier?: string,
     *     language?: string,
     *     name?: string,
     *     agency_id?: ?string,
     *     group_id?: ?string,
     *     talent_id?: ?string,
     *     lyricist?: string,
     *     normalized_lyricist?: string,
     *     composer?: string,
     *     normalized_composer?: string,
     *     release_date?: ?string,
     *     lyrics?: string,
     *     overview?: string,
     *     cover_image_path?: ?string,
     *     music_video_link?: ?string,
     *     normalized_name?: string,
     *     version?: int
     * } $overrides
     */
    public static function create(string $songId, array $overrides = []): void
    {
        $name = $overrides['name'] ?? 'LALALALA';
        $lyricist = $overrides['lyricist'] ?? 'Bang Chan, Changbin, Han';
        $composer = $overrides['composer'] ?? 'Bang Chan, Changbin, Han';

        DB::table('songs')->insert([
            'id' => $songId,
            'translation_set_identifier' => $overrides['translation_set_identifier'] ?? StrTestHelper::generateUuid(),
            'language' => $overrides['language'] ?? 'ko',
            'name' => $name,
            'normalized_name' => $overrides['normalized_name'] ?? mb_strtolower($name, 'UTF-8'),
            'agency_id' => $overrides['agency_id'] ?? null,
            'lyricist' => $lyricist,
            'normalized_lyricist' => $overrides['normalized_lyricist'] ?? mb_strtolower($lyricist, 'UTF-8'),
            'composer' => $composer,
            'normalized_composer' => $overrides['normalized_composer'] ?? mb_strtolower($composer, 'UTF-8'),
            'release_date' => $overrides['release_date'] ?? null,
            'lyrics' => $overrides['lyrics'] ?? '',
            'overview' => $overrides['overview'] ?? 'Stray Kids樂-STAR title track.',
            'cover_image_path' => $overrides['cover_image_path'] ?? null,
            'music_video_link' => $overrides['music_video_link'] ?? null,
            'version' => $overrides['version'] ?? 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if (isset($overrides['group_id'])) {
            DB::table('song_group')->insert([
                'song_id' => $songId,
                'group_id' => $overrides['group_id'],
            ]);
        }

        if (isset($overrides['talent_id'])) {
            DB::table('song_talent')->insert([
                'song_id' => $songId,
                'talent_id' => $overrides['talent_id'],
            ]);
        }
    }
}
