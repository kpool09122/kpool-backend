<?php

declare(strict_types=1);

namespace Tests\Helper;

use Illuminate\Support\Facades\DB;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

class CreateVideoLinkCollectionStatus
{
    /**
     * @param array{
     *     resource_type?: string,
     *     resource_identifier?: string,
     *     last_collected_at?: string|null,
     * } $overrides
     */
    public static function create(string $id, array $overrides = []): void
    {
        DB::table('video_link_collection_statuses')->insert([
            'id' => $id,
            'resource_type' => $overrides['resource_type'] ?? ResourceType::TALENT->value,
            'resource_identifier' => $overrides['resource_identifier'] ?? StrTestHelper::generateUuid(),
            'last_collected_at' => $overrides['last_collected_at'] ?? null,
            'created_at' => now(),
        ]);
    }
}
