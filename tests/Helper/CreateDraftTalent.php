<?php

declare(strict_types=1);

namespace Tests\Helper;

use Illuminate\Support\Facades\DB;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;

class CreateDraftTalent
{
    /**
     * @param array{
     *     published_id?: ?string,
     *     translation_set_identifier?: string,
     *     slug?: string,
     *     editor_id?: string,
     *     language?: string,
     *     name?: string,
     *     real_name?: string,
     *     agency_id?: ?string,
     *     group_identifiers?: array<string>,
     *     birthday?: ?string,
     *     career?: string,
     *     image_link?: ?string,
     *     status?: string,
     *     approver_id?: ?string,
     *     merger_id?: ?string
     * } $overrides
     */
    public static function create(string $draftTalentId, array $overrides = []): void
    {
        DB::table('draft_talents')->insert([
            'id' => $draftTalentId,
            'published_id' => $overrides['published_id'] ?? null,
            'translation_set_identifier' => $overrides['translation_set_identifier'] ?? StrTestHelper::generateUuid(),
            'slug' => $overrides['slug'] ?? 'hyunjin',
            'editor_id' => $overrides['editor_id'] ?? StrTestHelper::generateUuid(),
            'language' => $overrides['language'] ?? 'ko',
            'name' => $overrides['name'] ?? '현진',
            'real_name' => $overrides['real_name'] ?? '황현진',
            'agency_id' => $overrides['agency_id'] ?? null,
            'birthday' => $overrides['birthday'] ?? null,
            'career' => $overrides['career'] ?? 'Stray Kids main dancer and lead rapper.',
            'image_link' => $overrides['image_link'] ?? null,
            'status' => $overrides['status'] ?? ApprovalStatus::Pending->value,
            'approver_id' => $overrides['approver_id'] ?? null,
            'merger_id' => $overrides['merger_id'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $groupIdentifiers = $overrides['group_identifiers'] ?? [];
        foreach ($groupIdentifiers as $groupId) {
            DB::table('draft_talent_group')->insert([
                'draft_talent_id' => $draftTalentId,
                'group_id' => $groupId,
            ]);
        }
    }
}
