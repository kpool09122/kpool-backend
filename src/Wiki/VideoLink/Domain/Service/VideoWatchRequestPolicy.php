<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Domain\Service;

use DateInterval;
use DateTimeImmutable;

final class VideoWatchRequestPolicy
{
    public const int WEEKLY_LIMIT = 3;

    public static function expiresAt(DateTimeImmutable $sentAt): DateTimeImmutable
    {
        return $sentAt->add(new DateInterval('PT168H'));
    }

    public static function cutoff(DateTimeImmutable $now): DateTimeImmutable
    {
        return $now->sub(new DateInterval('PT168H'));
    }

    /** @return 'duration_not_eligible'|'like_required'|'resend_not_available'|'weekly_limit_reached'|null */
    public static function rejectionReason(?int $duration, bool $liked, int $recentCount, ?DateTimeImmutable $lastSentAt, DateTimeImmutable $now): ?string
    {
        if ($duration === null || $duration > 30) {
            return 'duration_not_eligible';
        }
        if (! $liked) {
            return 'like_required';
        }
        if ($lastSentAt !== null && self::expiresAt($lastSentAt) > $now) {
            return 'resend_not_available';
        }
        if ($recentCount >= self::WEEKLY_LIMIT) {
            return 'weekly_limit_reached';
        }

        return null;
    }
}
