<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WikiEditorSampleSeeder extends Seeder
{
    private const string GROUP_PUBLISHED_WIKI_ID = '01965bb2-bcc9-7c6f-8b90-89f7f217f001';
    private const string GROUP_DRAFT_WIKI_ID = '01965bb2-bcc9-7c6f-8b90-89f7f217f002';
    private const string GROUP_TRANSLATION_SET_ID = '01965bb2-bcc9-7c6f-8b90-89f7f217f003';
    private const string EDITOR_ID = '01965bb2-bcc9-7c6f-8b90-89f7f217f004';

    /**
     * Repo 内にフロントの mock fixture は見当たらないため、
     * 編集画面確認に必要な TWICE の 9 メンバー構成を最小サンプルとして固定投入する。
     *
     * @var array<int, array{
     *     key: string,
     *     published_wiki_id: string,
     *     draft_wiki_id: string,
     *     translation_set_identifier: string,
     *     slug: string,
     *     name: string,
     *     normalized_name: string,
     *     real_name: string,
     *     normalized_real_name: string,
     *     birthday: string,
     *     representative_symbol: string,
     *     position: string,
     *     mbti: ?string,
     *     zodiac_sign: string,
     *     height: int,
     *     blood_type: string
     * }>
     */
    private const array TALENTS = [
        [
            'key' => 'nayeon',
            'published_wiki_id' => '01965bb2-bcc9-7c6f-8b90-89f7f217f101',
            'draft_wiki_id' => '01965bb2-bcc9-7c6f-8b90-89f7f217f102',
            'translation_set_identifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f103',
            'slug' => 'tl-nayeon',
            'name' => 'Nayeon',
            'normalized_name' => 'nayeon',
            'real_name' => 'Im Nayeon',
            'normalized_real_name' => 'im nayeon',
            'birthday' => '1995-09-22',
            'representative_symbol' => 'Bunny',
            'position' => 'Lead Vocalist, Lead Dancer, Center',
            'mbti' => 'ISFP',
            'zodiac_sign' => 'Virgo',
            'height' => 163,
            'blood_type' => 'A',
        ],
        [
            'key' => 'jeongyeon',
            'published_wiki_id' => '01965bb2-bcc9-7c6f-8b90-89f7f217f111',
            'draft_wiki_id' => '01965bb2-bcc9-7c6f-8b90-89f7f217f112',
            'translation_set_identifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f113',
            'slug' => 'tl-jeongyeon',
            'name' => 'Jeongyeon',
            'normalized_name' => 'jeongyeon',
            'real_name' => 'Yoo Jeongyeon',
            'normalized_real_name' => 'yoo jeongyeon',
            'birthday' => '1996-11-01',
            'representative_symbol' => 'Dog',
            'position' => 'Lead Vocalist',
            'mbti' => 'ISFJ',
            'zodiac_sign' => 'Scorpio',
            'height' => 169,
            'blood_type' => 'O',
        ],
        [
            'key' => 'momo',
            'published_wiki_id' => '01965bb2-bcc9-7c6f-8b90-89f7f217f121',
            'draft_wiki_id' => '01965bb2-bcc9-7c6f-8b90-89f7f217f122',
            'translation_set_identifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f123',
            'slug' => 'tl-momo',
            'name' => 'Momo',
            'normalized_name' => 'momo',
            'real_name' => 'Hirai Momo',
            'normalized_real_name' => 'hirai momo',
            'birthday' => '1996-11-09',
            'representative_symbol' => 'Peach',
            'position' => 'Main Dancer, Sub Vocalist, Sub Rapper',
            'mbti' => 'INFP',
            'zodiac_sign' => 'Scorpio',
            'height' => 167,
            'blood_type' => 'A',
        ],
        [
            'key' => 'sana',
            'published_wiki_id' => '01965bb2-bcc9-7c6f-8b90-89f7f217f131',
            'draft_wiki_id' => '01965bb2-bcc9-7c6f-8b90-89f7f217f132',
            'translation_set_identifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f133',
            'slug' => 'tl-sana',
            'name' => 'Sana',
            'normalized_name' => 'sana',
            'real_name' => 'Minatozaki Sana',
            'normalized_real_name' => 'minatozaki sana',
            'birthday' => '1996-12-29',
            'representative_symbol' => 'Squirrel',
            'position' => 'Sub Vocalist',
            'mbti' => 'ENFP',
            'zodiac_sign' => 'Capricorn',
            'height' => 164,
            'blood_type' => 'B',
        ],
        [
            'key' => 'jihyo',
            'published_wiki_id' => '01965bb2-bcc9-7c6f-8b90-89f7f217f141',
            'draft_wiki_id' => '01965bb2-bcc9-7c6f-8b90-89f7f217f142',
            'translation_set_identifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f143',
            'slug' => 'tl-jihyo',
            'name' => 'Jihyo',
            'normalized_name' => 'jihyo',
            'real_name' => 'Park Jihyo',
            'normalized_real_name' => 'park jihyo',
            'birthday' => '1997-02-01',
            'representative_symbol' => 'Apricot',
            'position' => 'Leader, Main Vocalist',
            'mbti' => 'ISFP',
            'zodiac_sign' => 'Aquarius',
            'height' => 160,
            'blood_type' => 'O',
        ],
        [
            'key' => 'mina',
            'published_wiki_id' => '01965bb2-bcc9-7c6f-8b90-89f7f217f151',
            'draft_wiki_id' => '01965bb2-bcc9-7c6f-8b90-89f7f217f152',
            'translation_set_identifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f153',
            'slug' => 'tl-mina',
            'name' => 'Mina',
            'normalized_name' => 'mina',
            'real_name' => 'Myoui Mina',
            'normalized_real_name' => 'myoui mina',
            'birthday' => '1997-03-24',
            'representative_symbol' => 'Penguin',
            'position' => 'Main Dancer, Sub Vocalist',
            'mbti' => 'ISFP',
            'zodiac_sign' => 'Aries',
            'height' => 163,
            'blood_type' => 'A',
        ],
        [
            'key' => 'dahyun',
            'published_wiki_id' => '01965bb2-bcc9-7c6f-8b90-89f7f217f161',
            'draft_wiki_id' => '01965bb2-bcc9-7c6f-8b90-89f7f217f162',
            'translation_set_identifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f163',
            'slug' => 'tl-dahyun',
            'name' => 'Dahyun',
            'normalized_name' => 'dahyun',
            'real_name' => 'Kim Dahyun',
            'normalized_real_name' => 'kim dahyun',
            'birthday' => '1998-05-28',
            'representative_symbol' => 'Tofu',
            'position' => 'Lead Rapper, Sub Vocalist',
            'mbti' => 'ISFJ',
            'zodiac_sign' => 'Gemini',
            'height' => 161,
            'blood_type' => 'O',
        ],
        [
            'key' => 'chaeyoung',
            'published_wiki_id' => '01965bb2-bcc9-7c6f-8b90-89f7f217f171',
            'draft_wiki_id' => '01965bb2-bcc9-7c6f-8b90-89f7f217f172',
            'translation_set_identifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f173',
            'slug' => 'tl-chaeyoung',
            'name' => 'Chaeyoung',
            'normalized_name' => 'chaeyoung',
            'real_name' => 'Son Chaeyoung',
            'normalized_real_name' => 'son chaeyoung',
            'birthday' => '1999-04-23',
            'representative_symbol' => 'Tiger',
            'position' => 'Main Rapper, Sub Vocalist',
            'mbti' => 'INFP',
            'zodiac_sign' => 'Taurus',
            'height' => 159,
            'blood_type' => 'B',
        ],
        [
            'key' => 'tzuyu',
            'published_wiki_id' => '01965bb2-bcc9-7c6f-8b90-89f7f217f181',
            'draft_wiki_id' => '01965bb2-bcc9-7c6f-8b90-89f7f217f182',
            'translation_set_identifier' => '01965bb2-bcc9-7c6f-8b90-89f7f217f183',
            'slug' => 'tl-tzuyu',
            'name' => 'Tzuyu',
            'normalized_name' => 'tzuyu',
            'real_name' => 'Chou Tzuyu',
            'normalized_real_name' => 'chou tzuyu',
            'birthday' => '1999-06-14',
            'representative_symbol' => 'Yoda',
            'position' => 'Lead Dancer, Sub Vocalist, Visual',
            'mbti' => 'ISFP',
            'zodiac_sign' => 'Gemini',
            'height' => 172,
            'blood_type' => 'A',
        ],
    ];

    public function run(): void
    {
        $this->seedPublishedGroup();
        $this->seedDraftGroup();

        foreach (self::TALENTS as $talent) {
            $this->seedPublishedTalent($talent);
            $this->seedDraftTalent($talent);
        }
    }

    private function seedPublishedGroup(): void
    {
        DB::table('wikis')->upsert([
            [
                'id' => self::GROUP_PUBLISHED_WIKI_ID,
                'translation_set_identifier' => self::GROUP_TRANSLATION_SET_ID,
                'slug' => 'gr-twice',
                'language' => 'ko',
                'resource_type' => 'group',
                'sections' => $this->groupSections('TWICE is a nine-member group sample used for the wiki editor.'),
                'theme_color' => '#FE5F8F',
                'version' => 1,
                'owner_account_id' => null,
                'editor_id' => self::EDITOR_ID,
                'approver_id' => null,
                'merger_id' => null,
                'source_editor_id' => null,
                'merged_at' => null,
                'translated_at' => null,
                'approved_at' => null,
                'published_at' => '2026-04-22 00:00:00',
                'created_at' => '2026-04-22 00:00:00',
                'updated_at' => '2026-04-22 00:00:00',
            ],
        ], ['id']);

        DB::table('wiki_group_basics')->upsert([
            [
                'wiki_id' => self::GROUP_PUBLISHED_WIKI_ID,
                'name' => 'TWICE',
                'normalized_name' => 'twice',
                'agency_identifier' => null,
                'group_type' => 'girl_group',
                'status' => 'active',
                'generation' => '3',
                'debut_date' => '2015-10-20',
                'disband_date' => null,
                'fandom_name' => 'ONCE',
                'official_colors' => json_encode(['#FE5F8F', '#FEE500'], JSON_THROW_ON_ERROR),
                'emoji' => '',
                'representative_symbol' => 'Candy Bong',
                'main_image_identifier' => null,
                'created_at' => '2026-04-22 00:00:00',
                'updated_at' => '2026-04-22 00:00:00',
            ],
        ], ['wiki_id']);
    }

    private function seedDraftGroup(): void
    {
        DB::table('draft_wikis')->upsert([
            [
                'id' => self::GROUP_DRAFT_WIKI_ID,
                'published_wiki_id' => self::GROUP_PUBLISHED_WIKI_ID,
                'translation_set_identifier' => self::GROUP_TRANSLATION_SET_ID,
                'slug' => 'gr-twice',
                'language' => 'ko',
                'resource_type' => 'group',
                'sections' => $this->groupSections('Draft sample for checking the TWICE group wiki editor state.'),
                'theme_color' => '#FE5F8F',
                'status' => 'pending',
                'editor_id' => self::EDITOR_ID,
                'approver_id' => null,
                'merger_id' => null,
                'source_editor_id' => null,
                'edited_at' => '2026-04-22 00:00:00',
                'merged_at' => null,
                'translated_at' => null,
                'approved_at' => null,
                'created_at' => '2026-04-22 00:00:00',
                'updated_at' => '2026-04-22 00:00:00',
            ],
        ], ['id']);

        DB::table('draft_wiki_group_basics')->upsert([
            [
                'wiki_id' => self::GROUP_DRAFT_WIKI_ID,
                'name' => 'TWICE',
                'normalized_name' => 'twice',
                'agency_identifier' => null,
                'group_type' => 'girl_group',
                'status' => 'active',
                'generation' => '3',
                'debut_date' => '2015-10-20',
                'disband_date' => null,
                'fandom_name' => 'ONCE',
                'official_colors' => json_encode(['#FE5F8F', '#FEE500'], JSON_THROW_ON_ERROR),
                'emoji' => '',
                'representative_symbol' => 'Candy Bong',
                'main_image_identifier' => null,
                'created_at' => '2026-04-22 00:00:00',
                'updated_at' => '2026-04-22 00:00:00',
            ],
        ], ['wiki_id']);
    }

    /**
     * @param array{
     *     published_wiki_id: string,
     *     translation_set_identifier: string,
     *     slug: string,
     *     name: string,
     *     normalized_name: string,
     *     real_name: string,
     *     normalized_real_name: string,
     *     birthday: string,
     *     representative_symbol: string,
     *     position: string,
     *     mbti: ?string,
     *     zodiac_sign: string,
     *     height: int,
     *     blood_type: string
     * } $talent
     */
    private function seedPublishedTalent(array $talent): void
    {
        DB::table('wikis')->upsert([
            [
                'id' => $talent['published_wiki_id'],
                'translation_set_identifier' => $talent['translation_set_identifier'],
                'slug' => $talent['slug'],
                'language' => 'ko',
                'resource_type' => 'talent',
                'sections' => $this->talentSections($talent['name'], false),
                'theme_color' => '#FE5F8F',
                'version' => 1,
                'owner_account_id' => null,
                'editor_id' => self::EDITOR_ID,
                'approver_id' => null,
                'merger_id' => null,
                'source_editor_id' => null,
                'merged_at' => null,
                'translated_at' => null,
                'approved_at' => null,
                'published_at' => '2026-04-22 00:00:00',
                'created_at' => '2026-04-22 00:00:00',
                'updated_at' => '2026-04-22 00:00:00',
            ],
        ], ['id']);

        DB::table('wiki_talent_basics')->upsert([
            [
                'wiki_id' => $talent['published_wiki_id'],
                'name' => $talent['name'],
                'normalized_name' => $talent['normalized_name'],
                'real_name' => $talent['real_name'],
                'normalized_real_name' => $talent['normalized_real_name'],
                'birthday' => $talent['birthday'],
                'agency_identifier' => null,
                'emoji' => '',
                'representative_symbol' => $talent['representative_symbol'],
                'position' => $talent['position'],
                'mbti' => $talent['mbti'],
                'zodiac_sign' => $talent['zodiac_sign'],
                'english_level' => null,
                'height' => $talent['height'],
                'blood_type' => $talent['blood_type'],
                'fandom_name' => 'ONCE',
                'profile_image_identifier' => null,
                'created_at' => '2026-04-22 00:00:00',
                'updated_at' => '2026-04-22 00:00:00',
            ],
        ], ['wiki_id']);

        DB::table('wiki_talent_basic_groups')->upsert([
            [
                'wiki_id' => $talent['published_wiki_id'],
                'group_identifier' => self::GROUP_PUBLISHED_WIKI_ID,
            ],
        ], ['wiki_id', 'group_identifier']);
    }

    /**
     * @param array{
     *     draft_wiki_id: string,
     *     published_wiki_id: string,
     *     translation_set_identifier: string,
     *     slug: string,
     *     name: string,
     *     normalized_name: string,
     *     real_name: string,
     *     normalized_real_name: string,
     *     birthday: string,
     *     representative_symbol: string,
     *     position: string,
     *     mbti: ?string,
     *     zodiac_sign: string,
     *     height: int,
     *     blood_type: string
     * } $talent
     */
    private function seedDraftTalent(array $talent): void
    {
        DB::table('draft_wikis')->upsert([
            [
                'id' => $talent['draft_wiki_id'],
                'published_wiki_id' => $talent['published_wiki_id'],
                'translation_set_identifier' => $talent['translation_set_identifier'],
                'slug' => $talent['slug'],
                'language' => 'ko',
                'resource_type' => 'talent',
                'sections' => $this->talentSections($talent['name'], true),
                'theme_color' => '#FE5F8F',
                'status' => 'pending',
                'editor_id' => self::EDITOR_ID,
                'approver_id' => null,
                'merger_id' => null,
                'source_editor_id' => null,
                'edited_at' => '2026-04-22 00:00:00',
                'merged_at' => null,
                'translated_at' => null,
                'approved_at' => null,
                'created_at' => '2026-04-22 00:00:00',
                'updated_at' => '2026-04-22 00:00:00',
            ],
        ], ['id']);

        DB::table('draft_wiki_talent_basics')->upsert([
            [
                'wiki_id' => $talent['draft_wiki_id'],
                'name' => $talent['name'],
                'normalized_name' => $talent['normalized_name'],
                'real_name' => $talent['real_name'],
                'normalized_real_name' => $talent['normalized_real_name'],
                'birthday' => $talent['birthday'],
                'agency_identifier' => null,
                'emoji' => '',
                'representative_symbol' => $talent['representative_symbol'],
                'position' => $talent['position'],
                'mbti' => $talent['mbti'],
                'zodiac_sign' => $talent['zodiac_sign'],
                'english_level' => null,
                'height' => $talent['height'],
                'blood_type' => $talent['blood_type'],
                'fandom_name' => 'ONCE',
                'profile_image_identifier' => null,
                'created_at' => '2026-04-22 00:00:00',
                'updated_at' => '2026-04-22 00:00:00',
            ],
        ], ['wiki_id']);

        DB::table('draft_wiki_talent_basic_groups')->upsert([
            [
                'wiki_id' => $talent['draft_wiki_id'],
                'group_identifier' => self::GROUP_PUBLISHED_WIKI_ID,
            ],
        ], ['wiki_id', 'group_identifier']);
    }

    private function groupSections(string $summary): string
    {
        return json_encode([
            [
                'id' => 'overview',
                'type' => 'plaintext',
                'title' => 'Overview',
                'content' => $summary,
            ],
        ], JSON_THROW_ON_ERROR);
    }

    private function talentSections(string $name, bool $isDraft): string
    {
        return json_encode([
            [
                'id' => 'overview',
                'type' => 'plaintext',
                'title' => 'Overview',
                'content' => $isDraft
                    ? "{$name} draft profile linked to the TWICE editor sample."
                    : "{$name} published profile linked to the TWICE group sample.",
            ],
        ], JSON_THROW_ON_ERROR);
    }
}
