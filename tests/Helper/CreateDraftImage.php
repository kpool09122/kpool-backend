<?php

declare(strict_types=1);

namespace Tests\Helper;

use Illuminate\Support\Facades\DB;
use Source\Wiki\Image\Domain\ValueObject\ImageUsage;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

class CreateDraftImage
{
    /**
     * @param array{
     *     published_id?: ?string,
     *     resource_type?: string,
     *     draft_resource_identifier?: string,
     *     editor_id?: string,
     *     image_path?: string,
     *     image_usage?: string,
     *     display_order?: int,
     *     source_url?: string,
     *     source_name?: string,
     *     alt_text?: string,
     *     status?: string,
     *     agreed_to_terms_at?: string,
     * } $overrides
     */
    public static function create(string $draftImageId, array $overrides = []): void
    {
        DB::table('draft_wiki_images')->insert([
            'id' => $draftImageId,
            'published_id' => $overrides['published_id'] ?? null,
            'resource_type' => $overrides['resource_type'] ?? ResourceType::TALENT->value,
            'draft_resource_identifier' => $overrides['draft_resource_identifier'] ?? StrTestHelper::generateUuid(),
            'editor_id' => $overrides['editor_id'] ?? StrTestHelper::generateUuid(),
            'image_path' => $overrides['image_path'] ?? '/images/test/sample.jpg',
            'image_usage' => $overrides['image_usage'] ?? ImageUsage::PROFILE->value,
            'display_order' => $overrides['display_order'] ?? 1,
            'source_url' => $overrides['source_url'] ?? 'https://example.com/source',
            'source_name' => $overrides['source_name'] ?? 'Example Source',
            'alt_text' => $overrides['alt_text'] ?? 'Test image',
            'status' => $overrides['status'] ?? ApprovalStatus::UnderReview->value,
            'agreed_to_terms_at' => $overrides['agreed_to_terms_at'] ?? '2024-01-01 00:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
