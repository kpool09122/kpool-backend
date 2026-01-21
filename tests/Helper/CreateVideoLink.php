<?php

declare(strict_types=1);

namespace Tests\Helper;

use Illuminate\Support\Facades\DB;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoUsage;

class CreateVideoLink
{
    /**
     * @param array{
     *     resource_type?: string,
     *     resource_identifier?: string,
     *     url?: string,
     *     video_usage?: string,
     *     title?: string,
     *     thumbnail_url?: string|null,
     *     published_at?: string|null,
     *     display_order?: int,
     * } $overrides
     */
    public static function create(string $videoLinkId, array $overrides = []): void
    {
        DB::table('video_links')->insert([
            'id' => $videoLinkId,
            'resource_type' => $overrides['resource_type'] ?? ResourceType::TALENT->value,
            'resource_identifier' => $overrides['resource_identifier'] ?? StrTestHelper::generateUuid(),
            'url' => $overrides['url'] ?? sprintf(
                'https://www.youtube.com/watch?v=%s',
                StrTestHelper::generateUuid()
            ),
            'video_usage' => $overrides['video_usage'] ?? VideoUsage::MUSIC_VIDEO->value,
            'title' => $overrides['title'] ?? 'Test Video',
            'thumbnail_url' => $overrides['thumbnail_url'] ?? null,
            'published_at' => $overrides['published_at'] ?? null,
            'display_order' => $overrides['display_order'] ?? 1,
            'created_at' => now(),
        ]);
    }
}
