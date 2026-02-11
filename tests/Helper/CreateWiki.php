<?php

declare(strict_types=1);

namespace Tests\Helper;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CreateWiki
{
    /**
     * @param array{
     *     translation_set_identifier?: string,
     *     slug?: string,
     *     language?: string,
     *     sections?: string,
     *     theme_color?: ?string,
     *     version?: int,
     *     owner_account_id?: ?string,
     *     editor_id?: ?string,
     *     approver_id?: ?string,
     *     merger_id?: ?string,
     *     source_editor_id?: ?string,
     *     merged_at?: ?string,
     *     translated_at?: ?string,
     *     approved_at?: ?string,
     *     published_at?: ?string,
     * } $overrides
     * @param array<string, mixed> $basicOverrides
     */
    public static function create(string $wikiId, string $resourceType, array $overrides = [], array $basicOverrides = []): void
    {
        DB::table('wikis')->insert([
            'id' => $wikiId,
            'translation_set_identifier' => $overrides['translation_set_identifier'] ?? StrTestHelper::generateUuid(),
            'slug' => $overrides['slug'] ?? 'test-wiki-' . substr($wikiId, 0, 8),
            'language' => $overrides['language'] ?? 'ko',
            'resource_type' => $resourceType,
            'sections' => $overrides['sections'] ?? json_encode([]),
            'theme_color' => $overrides['theme_color'] ?? null,
            'version' => $overrides['version'] ?? 1,
            'owner_account_id' => $overrides['owner_account_id'] ?? null,
            'editor_id' => $overrides['editor_id'] ?? null,
            'approver_id' => $overrides['approver_id'] ?? null,
            'merger_id' => $overrides['merger_id'] ?? null,
            'source_editor_id' => $overrides['source_editor_id'] ?? null,
            'merged_at' => $overrides['merged_at'] ?? null,
            'translated_at' => $overrides['translated_at'] ?? null,
            'approved_at' => $overrides['approved_at'] ?? null,
            'published_at' => $overrides['published_at'] ?? null,
        ]);

        match ($resourceType) {
            'group' => self::createGroupBasic($wikiId, $basicOverrides),
            'talent' => self::createTalentBasic($wikiId, $basicOverrides),
            'agency' => self::createAgencyBasic($wikiId, $basicOverrides),
            'song' => self::createSongBasic($wikiId, $basicOverrides),
            default => throw new InvalidArgumentException("Unknown resource type: {$resourceType}"),
        };
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private static function createGroupBasic(string $wikiId, array $overrides = []): void
    {
        DB::table('wiki_group_basics')->insert([
            'wiki_id' => $wikiId,
            'name' => $overrides['name'] ?? 'TWICE',
            'normalized_name' => $overrides['normalized_name'] ?? 'twice',
            'agency_identifier' => $overrides['agency_identifier'] ?? null,
            'group_type' => $overrides['group_type'] ?? null,
            'status' => $overrides['status'] ?? null,
            'generation' => $overrides['generation'] ?? null,
            'debut_date' => $overrides['debut_date'] ?? null,
            'disband_date' => $overrides['disband_date'] ?? null,
            'fandom_name' => $overrides['fandom_name'] ?? 'ONCE',
            'official_colors' => $overrides['official_colors'] ?? json_encode([]),
            'emoji' => $overrides['emoji'] ?? '',
            'representative_symbol' => $overrides['representative_symbol'] ?? '',
            'main_image_identifier' => $overrides['main_image_identifier'] ?? null,
        ]);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private static function createTalentBasic(string $wikiId, array $overrides = []): void
    {
        $groupIdentifiers = isset($overrides['group_identifiers'])
            ? json_decode($overrides['group_identifiers'], true)
            : [];

        DB::table('wiki_talent_basics')->insert([
            'wiki_id' => $wikiId,
            'name' => $overrides['name'] ?? '채영',
            'normalized_name' => $overrides['normalized_name'] ?? 'chaeyoung',
            'real_name' => $overrides['real_name'] ?? '손채영',
            'normalized_real_name' => $overrides['normalized_real_name'] ?? 'sonchaeyoung',
            'birthday' => $overrides['birthday'] ?? null,
            'agency_identifier' => $overrides['agency_identifier'] ?? null,
            'emoji' => $overrides['emoji'] ?? '',
            'representative_symbol' => $overrides['representative_symbol'] ?? '',
            'position' => $overrides['position'] ?? '',
            'mbti' => $overrides['mbti'] ?? null,
            'zodiac_sign' => $overrides['zodiac_sign'] ?? null,
            'english_level' => $overrides['english_level'] ?? null,
            'height' => $overrides['height'] ?? null,
            'blood_type' => $overrides['blood_type'] ?? null,
            'fandom_name' => $overrides['fandom_name'] ?? '',
            'profile_image_identifier' => $overrides['profile_image_identifier'] ?? null,
        ]);

        foreach ($groupIdentifiers as $groupId) {
            DB::table('wiki_talent_basic_groups')->insert([
                'wiki_id' => $wikiId,
                'group_identifier' => $groupId,
            ]);
        }
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private static function createAgencyBasic(string $wikiId, array $overrides = []): void
    {
        DB::table('wiki_agency_basics')->insert([
            'wiki_id' => $wikiId,
            'name' => $overrides['name'] ?? 'JYP Entertainment',
            'normalized_name' => $overrides['normalized_name'] ?? 'jyp entertainment',
            'ceo' => $overrides['ceo'] ?? 'J.Y. Park',
            'normalized_ceo' => $overrides['normalized_ceo'] ?? 'j.y. park',
            'founded_in' => $overrides['founded_in'] ?? null,
            'parent_agency_identifier' => $overrides['parent_agency_identifier'] ?? null,
            'status' => $overrides['status'] ?? null,
            'logo_image_identifier' => $overrides['logo_image_identifier'] ?? null,
            'official_website' => $overrides['official_website'] ?? null,
            'social_links' => $overrides['social_links'] ?? json_encode([]),
        ]);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private static function createSongBasic(string $wikiId, array $overrides = []): void
    {
        $groupIdentifiers = isset($overrides['group_identifiers'])
            ? json_decode($overrides['group_identifiers'], true)
            : [];
        $talentIdentifiers = isset($overrides['talent_identifiers'])
            ? json_decode($overrides['talent_identifiers'], true)
            : [];

        DB::table('wiki_song_basics')->insert([
            'wiki_id' => $wikiId,
            'name' => $overrides['name'] ?? 'TT',
            'normalized_name' => $overrides['normalized_name'] ?? 'tt',
            'song_type' => $overrides['song_type'] ?? null,
            'genres' => $overrides['genres'] ?? json_encode([]),
            'agency_identifier' => $overrides['agency_identifier'] ?? null,
            'release_date' => $overrides['release_date'] ?? null,
            'album_name' => $overrides['album_name'] ?? null,
            'cover_image_identifier' => $overrides['cover_image_identifier'] ?? null,
            'lyricist' => $overrides['lyricist'] ?? 'Black Eyed Pilseung',
            'normalized_lyricist' => $overrides['normalized_lyricist'] ?? 'black eyed pilseung',
            'composer' => $overrides['composer'] ?? 'Black Eyed Pilseung',
            'normalized_composer' => $overrides['normalized_composer'] ?? 'black eyed pilseung',
            'arranger' => $overrides['arranger'] ?? 'Rado',
            'normalized_arranger' => $overrides['normalized_arranger'] ?? 'rado',
        ]);

        foreach ($groupIdentifiers as $groupId) {
            DB::table('wiki_song_basic_groups')->insert([
                'wiki_id' => $wikiId,
                'group_identifier' => $groupId,
            ]);
        }

        foreach ($talentIdentifiers as $talentId) {
            DB::table('wiki_song_basic_talents')->insert([
                'wiki_id' => $wikiId,
                'talent_identifier' => $talentId,
            ]);
        }
    }
}
