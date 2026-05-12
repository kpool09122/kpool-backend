<?php

declare(strict_types=1);

namespace Tests\Helper;

use Illuminate\Support\Facades\DB;
use Source\Wiki\Image\Domain\ValueObject\ImageUsage;

class CreateImageSnapshot
{
    /**
     * @param array{
     *     image_id?: string,
     *     translation_set_identifier?: string,
     *     image_path?: string,
     *     image_usage?: string,
     *     display_order?: int,
     *     source_url?: string,
     *     source_name?: string,
     *     alt_text?: string,
     *     uploader_id?: string,
     *     uploaded_at?: string,
     *     rights_confirmation_agreed?: bool,
     *     approver_id?: string|null,
     *     approved_at?: string|null,
     *     updater_id?: string|null,
     *     updated_at?: string|null,
     * } $overrides
     */
    public static function create(string $snapshotId, array $overrides = []): void
    {
        DB::table('wiki_image_snapshots')->insert([
            'id' => $snapshotId,
            'image_id' => $overrides['image_id'] ?? StrTestHelper::generateUuid(),
            'translation_set_identifier' => $overrides['translation_set_identifier'] ?? StrTestHelper::generateUuid(),
            'image_path' => $overrides['image_path'] ?? '/images/test/snapshot.jpg',
            'image_usage' => $overrides['image_usage'] ?? ImageUsage::PROFILE->value,
            'display_order' => $overrides['display_order'] ?? 1,
            'source_url' => $overrides['source_url'] ?? 'https://example.com/source',
            'source_name' => $overrides['source_name'] ?? 'Example Source',
            'alt_text' => $overrides['alt_text'] ?? 'Test image',
            'uploader_id' => $overrides['uploader_id'] ?? StrTestHelper::generateUuid(),
            'uploaded_at' => $overrides['uploaded_at'] ?? '2024-01-01 00:00:00',
            'rights_confirmation_agreed' => $overrides['rights_confirmation_agreed'] ?? true,
            'approver_id' => $overrides['approver_id'] ?? StrTestHelper::generateUuid(),
            'approved_at' => $overrides['approved_at'] ?? '2024-01-01 00:00:00',
            'updater_id' => $overrides['updater_id'] ?? null,
            'updated_at' => $overrides['updated_at'] ?? null,
        ]);
    }
}
