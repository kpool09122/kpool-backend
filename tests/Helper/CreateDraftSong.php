<?php

declare(strict_types=1);

namespace Tests\Helper;

use Illuminate\Support\Facades\DB;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;

class CreateDraftSong
{
    /**
     * @param array{
     *     published_id?: ?string,
     *     translation_set_identifier?: string,
     *     editor_id?: string,
     *     language?: string,
     *     name?: string,
     *     agency_id?: ?string,
     *     group_id?: ?string,
     *     talent_id?: ?string,
     *     lyricist?: string,
     *     composer?: string,
     *     release_date?: ?string,
     *     lyrics?: string,
     *     overview?: string,
     *     cover_image_path?: ?string,
     *     music_video_link?: ?string,
     *     status?: string
     * } $overrides
     */
    public static function create(string $draftSongId, array $overrides = []): void
    {
        DB::table('draft_songs')->insert([
            'id' => $draftSongId,
            'published_id' => $overrides['published_id'] ?? null,
            'translation_set_identifier' => $overrides['translation_set_identifier'] ?? StrTestHelper::generateUuid(),
            'editor_id' => $overrides['editor_id'] ?? StrTestHelper::generateUuid(),
            'language' => $overrides['language'] ?? 'ko',
            'name' => $overrides['name'] ?? 'Hype Boy',
            'agency_id' => $overrides['agency_id'] ?? null,
            'lyricist' => $overrides['lyricist'] ?? 'Gigi',
            'composer' => $overrides['composer'] ?? '250',
            'release_date' => $overrides['release_date'] ?? null,
            'lyrics' => $overrides['lyrics'] ?? '',
            'overview' => $overrides['overview'] ?? 'NewJeans debut single.',
            'cover_image_path' => $overrides['cover_image_path'] ?? null,
            'music_video_link' => $overrides['music_video_link'] ?? null,
            'status' => $overrides['status'] ?? ApprovalStatus::Pending->value,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if (isset($overrides['group_id'])) {
            DB::table('draft_song_group')->insert([
                'draft_song_id' => $draftSongId,
                'group_id' => $overrides['group_id'],
            ]);
        }

        if (isset($overrides['talent_id'])) {
            DB::table('draft_song_talent')->insert([
                'draft_song_id' => $draftSongId,
                'talent_id' => $overrides['talent_id'],
            ]);
        }
    }
}
