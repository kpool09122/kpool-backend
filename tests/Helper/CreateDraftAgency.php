<?php

declare(strict_types=1);

namespace Tests\Helper;

use Illuminate\Support\Facades\DB;

class CreateDraftAgency
{
    /**
     * @param array{
     *     published_id?: ?string,
     *     translation_set_identifier?: string,
     *     slug?: string,
     *     editor_id?: string,
     *     language?: string,
     *     name?: string,
     *     normalized_name?: string,
     *     CEO?: string,
     *     normalized_CEO?: string,
     *     founded_in?: ?string,
     *     description?: string,
     *     status?: string,
     *     approver_id?: ?string,
     *     merger_id?: ?string
     * } $overrides
     */
    public static function create(string $draftAgencyId, array $overrides = []): void
    {
        DB::table('draft_agencies')->insert([
            'id' => $draftAgencyId,
            'published_id' => $overrides['published_id'] ?? null,
            'translation_set_identifier' => $overrides['translation_set_identifier'] ?? StrTestHelper::generateUuid(),
            'slug' => $overrides['slug'] ?? 'jyp-entertainment',
            'editor_id' => $overrides['editor_id'] ?? StrTestHelper::generateUuid(),
            'language' => $overrides['language'] ?? 'ko',
            'name' => $overrides['name'] ?? 'JYP엔터테인먼트',
            'normalized_name' => $overrides['normalized_name'] ?? 'jypㅇㅌㅌㅇㅁㅌ',
            'CEO' => $overrides['CEO'] ?? 'J.Y. Park',
            'normalized_CEO' => $overrides['normalized_CEO'] ?? 'j.y. park',
            'founded_in' => $overrides['founded_in'] ?? '1997-04-25',
            'description' => $overrides['description'] ?? 'JYP Entertainment is a South Korean entertainment company.',
            'status' => $overrides['status'] ?? 'pending',
            'approver_id' => $overrides['approver_id'] ?? null,
            'merger_id' => $overrides['merger_id'] ?? null,
        ]);
    }
}
