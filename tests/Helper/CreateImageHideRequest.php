<?php

declare(strict_types=1);

namespace Tests\Helper;

use Illuminate\Support\Facades\DB;
use Source\Wiki\Image\Domain\ValueObject\ImageHideRequestStatus;

class CreateImageHideRequest
{
    /**
     * @param array{
     *     image_id?: string,
     *     requester_name?: string,
     *     requester_email?: string,
     *     reason?: string,
     *     status?: string,
     *     requested_at?: mixed,
     *     reviewer_id?: string|null,
     *     reviewed_at?: mixed,
     *     reviewer_comment?: string|null,
     * } $overrides
     */
    public static function create(string $requestId, array $overrides = []): void
    {
        DB::table('image_hide_requests')->insert([
            'id' => $requestId,
            'image_id' => $overrides['image_id'] ?? StrTestHelper::generateUuid(),
            'requester_name' => $overrides['requester_name'] ?? 'Test Requester',
            'requester_email' => $overrides['requester_email'] ?? 'requester@example.com',
            'reason' => $overrides['reason'] ?? 'Privacy concern',
            'status' => $overrides['status'] ?? ImageHideRequestStatus::PENDING->value,
            'requested_at' => $overrides['requested_at'] ?? now(),
            'reviewer_id' => $overrides['reviewer_id'] ?? null,
            'reviewed_at' => $overrides['reviewed_at'] ?? null,
            'reviewer_comment' => $overrides['reviewer_comment'] ?? null,
        ]);
    }
}
