<?php

declare(strict_types=1);

namespace Tests\Helper;

use Illuminate\Support\Facades\DB;
use Source\Wiki\Image\Domain\ValueObject\ImageUsage;
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
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
