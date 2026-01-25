<?php

declare(strict_types=1);

namespace Tests\Helper;

use Illuminate\Support\Facades\DB;

class CreateAgency
{
    /**
     * @param array{
     *     translation_set_identifier?: string,
     *     slug?: string,
     *     language?: string,
     *     name?: string,
     *     normalized_name?: string,
     *     CEO?: string,
     *     normalized_CEO?: string,
     *     founded_in?: ?string,
     *     description?: string,
     *     version?: int
     * } $overrides
     */
    public static function create(string $agencyId, array $overrides = []): void
    {
        DB::table('agencies')->insert([
            'id' => $agencyId,
            'translation_set_identifier' => $overrides['translation_set_identifier'] ?? StrTestHelper::generateUuid(),
            'slug' => $overrides['slug'] ?? 'jyp-entertainment',
            'language' => $overrides['language'] ?? 'ko',
            'name' => $overrides['name'] ?? 'JYP엔터테인먼트',
            'normalized_name' => $overrides['normalized_name'] ?? 'jypㅇㅌㅌㅇㅁㅌ',
            'CEO' => $overrides['CEO'] ?? 'J.Y. Park',
            'normalized_CEO' => $overrides['normalized_CEO'] ?? 'j.y. park',
            'founded_in' => $overrides['founded_in'] ?? '1997-04-25',
            'description' => $overrides['description'] ?? 'JYP Entertainment is a South Korean entertainment company.',
            'version' => $overrides['version'] ?? 1,
        ]);
    }
}
