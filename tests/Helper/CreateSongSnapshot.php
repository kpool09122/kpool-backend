<?php

declare(strict_types=1);

namespace Tests\Helper;

use Illuminate\Support\Facades\DB;

class CreateSongSnapshot
{
    /**
     * @param array{
     *     song_id?: string,
     *     translation_set_identifier?: string,
     *     language?: string,
     *     name?: string,
     *     agency_id?: ?string,
     *     belong_identifiers?: array<int, string>,
     *     lyricist?: string,
     *     composer?: string,
     *     release_date?: ?string,
     *     overview?: string,
     *     cover_image_path?: ?string,
     *     music_video_link?: ?string,
     *     version?: int,
     *     created_at?: string
     * } $overrides
     */
    public static function create(string $snapshotId, array $overrides = []): void
    {
        DB::table('song_snapshots')->insert([
            'id' => $snapshotId,
            'song_id' => $overrides['song_id'] ?? StrTestHelper::generateUuid(),
            'translation_set_identifier' => $overrides['translation_set_identifier'] ?? StrTestHelper::generateUuid(),
            'language' => $overrides['language'] ?? 'ko',
            'name' => $overrides['name'] ?? 'TT',
            'agency_id' => $overrides['agency_id'] ?? StrTestHelper::generateUuid(),
            'belong_identifiers' => json_encode($overrides['belong_identifiers'] ?? []),
            'lyricist' => $overrides['lyricist'] ?? '블랙아이드필승',
            'composer' => $overrides['composer'] ?? 'Sam Lewis',
            'release_date' => $overrides['release_date'] ?? '2016-10-24',
            'overview' => $overrides['overview'] ?? 'TT is a song by TWICE.',
            'cover_image_path' => $overrides['cover_image_path'] ?? '/resources/public/images/tt.webp',
            'music_video_link' => $overrides['music_video_link'] ?? 'https://example.youtube.com/watch?v=dQw4w9WgXcQ',
            'version' => $overrides['version'] ?? 1,
            'created_at' => $overrides['created_at'] ?? '2024-01-01 00:00:00',
        ]);
    }
}
