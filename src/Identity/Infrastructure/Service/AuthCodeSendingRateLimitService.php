<?php

declare(strict_types=1);

namespace Source\Identity\Infrastructure\Service;

use Illuminate\Support\Facades\Redis;
use Source\Identity\Application\Service\AuthCodeSendingRateLimitServiceInterface;
use Source\Identity\Application\Service\EmailSendingStatus;
use Source\Shared\Domain\Support\TypedValue;
use Source\Shared\Domain\ValueObject\Email;

class AuthCodeSendingRateLimitService implements AuthCodeSendingRateLimitServiceInterface
{
    private const int MAX_SENDS = 5;
    private const int COOLDOWN_SECONDS = 60;
    private const int WINDOW_SECONDS = 3600;

    public function reserve(Email $email): EmailSendingStatus
    {
        $hash = hash('sha256', strtolower((string) $email));
        $countKey = 'auth_code_email_sends:' . $hash;
        $cooldownKey = 'auth_code_email_cooldown:' . $hash;
        $count = TypedValue::numericInt(Redis::get($countKey) ?? '0');
        $windowTtl = (int) Redis::ttl($countKey);
        if ($windowTtl === -2) {
            $count = 0;
        } elseif ($count > 0 && $windowTtl === -1) {
            Redis::expire($countKey, self::WINDOW_SECONDS);
            $windowTtl = self::WINDOW_SECONDS;
        }
        $windowTtl = max(0, $windowTtl);

        if ($count >= self::MAX_SENDS) {
            return new EmailSendingStatus(false, 0, $windowTtl);
        }

        $cooldownTtl = max(0, (int) Redis::ttl($cooldownKey));
        if ($cooldownTtl > 0) {
            return new EmailSendingStatus(false, self::MAX_SENDS - $count, $cooldownTtl);
        }

        $count = (int) Redis::incr($countKey);
        if ($count > self::MAX_SENDS) {
            Redis::decr($countKey);

            return new EmailSendingStatus(false, 0, max(0, (int) Redis::ttl($countKey)));
        }
        if ($count === 1) {
            Redis::expire($countKey, self::WINDOW_SECONDS);
            $windowTtl = self::WINDOW_SECONDS;
        } else {
            $windowTtl = max(0, (int) Redis::ttl($countKey));
        }
        Redis::setex($cooldownKey, self::COOLDOWN_SECONDS, '1');

        $remaining = max(0, self::MAX_SENDS - $count);

        return new EmailSendingStatus(
            true,
            $remaining,
            $remaining === 0 ? $windowTtl : self::COOLDOWN_SECONDS,
        );
    }
}
