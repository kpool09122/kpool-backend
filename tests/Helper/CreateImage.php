<?php

declare(strict_types=1);

namespace Tests\Helper;

use Illuminate\Support\Facades\DB;
use Source\Wiki\Image\Domain\ValueObject\ImageUsage;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

class CreateImage
{
    /**
     * @param array{
     *     resource_type?: string,
     *     resource_identifier?: string,
     *     image_path?: string,
     *     image_usage?: string,
     *     display_order?: int,
     * } $overrides
     */
    public static function create(string $imageId, array $overrides = []): void
    {
        DB::table('wiki_images')->insert([
            'id' => $imageId,
            'resource_type' => $overrides['resource_type'] ?? ResourceType::TALENT->value,
            'resource_identifier' => $overrides['resource_identifier'] ?? StrTestHelper::generateUuid(),
            'image_path' => $overrides['image_path'] ?? '/images/test/sample.jpg',
            'image_usage' => $overrides['image_usage'] ?? ImageUsage::PROFILE->value,
            'display_order' => $overrides['display_order'] ?? 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
