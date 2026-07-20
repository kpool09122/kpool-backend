<?php

declare(strict_types=1);

namespace Tests\Helper;

use Illuminate\Support\Facades\DB;

class CreateImageDeletionRequest
{
    /**
     * @param array{
     *     image_id?: string,
     *     requester_name?: string,
     *     requester_email?: string,
     *     reason?: string,
     *     requested_at?: mixed,
     *     reviewer_id?: string|null,
     *     reviewed_at?: mixed,
     *     reject_reason?: string|null,
     * } $overrides
     */
    public static function create(string $requestId, array $overrides = []): void
    {
        DB::table('image_deletion_requests')->insert([
            'id' => $requestId,
            'image_id' => $overrides['image_id'] ?? StrTestHelper::generateUuid(),
            'requester_name' => $overrides['requester_name'] ?? 'Test Requester',
            'requester_email' => $overrides['requester_email'] ?? 'requester@example.com',
            'reason' => $overrides['reason'] ?? 'Privacy concern',
            'requested_at' => $overrides['requested_at'] ?? now(),
            'reviewer_id' => $overrides['reviewer_id'] ?? null,
            'reviewed_at' => $overrides['reviewed_at'] ?? null,
            'reject_reason' => $overrides['reject_reason'] ?? null,
        ]);
    }
}
