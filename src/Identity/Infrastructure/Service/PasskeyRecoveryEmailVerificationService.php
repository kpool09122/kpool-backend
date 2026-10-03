<?php

declare(strict_types=1);

namespace Source\Identity\Infrastructure\Service;

use Application\Mail\PasskeyRecoveryCodeMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redis;
use Psr\Log\LoggerInterface;
use Source\Identity\Application\Service\EmailSendingStatus;
use Source\Identity\Application\Service\PasskeyRecovery\PasskeyRecoveryEmailVerificationServiceInterface;
use Source\Identity\Domain\Exception\PasskeyRecoveryVerificationFailedException;
use Source\Identity\Domain\ValueObject\AuthCode;
use Source\Shared\Domain\Support\TypedValue;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Throwable;

class PasskeyRecoveryEmailVerificationService implements PasskeyRecoveryEmailVerificationServiceInterface
{
    private const int TTL_SECONDS = 900;
    private const int COOLDOWN_SECONDS = 60;
    private const int MAX_SENDS = 5;
    private const int MAX_ATTEMPTS = 5;

    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function send(Email $email, ?IdentityIdentifier $identityIdentifier, Language $language): EmailSendingStatus
    {
        $hash = $this->hash($email);
        $countKey = 'passkey_recovery_email_sends:' . $hash;
        $cooldownKey = 'passkey_recovery_email_cooldown:' . $hash;
        $count = TypedValue::numericInt(Redis::get($countKey) ?? '0');
        $windowTtl = max(0, (int) Redis::ttl($countKey));
        if ($count >= self::MAX_SENDS) {
            return new EmailSendingStatus(false, 0, $windowTtl);
        }
        $cooldownTtl = max(0, (int) Redis::ttl($cooldownKey));
        if ($cooldownTtl > 0) {
            return new EmailSendingStatus(false, self::MAX_SENDS - $count, $cooldownTtl);
        }

        $count = (int) Redis::incr($countKey);
        if ($count === 1) {
            Redis::expire($countKey, 3600);
            $windowTtl = 3600;
        } else {
            $windowTtl = max(0, (int) Redis::ttl($countKey));
        }
        Redis::setex($cooldownKey, self::COOLDOWN_SECONDS, '1');

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        Redis::setex('passkey_recovery_email:' . $hash, self::TTL_SECONDS, json_encode([
            'identity_id' => $identityIdentifier === null ? null : (string) $identityIdentifier,
            'code_hash' => hash_hmac('sha256', $code, config()->string('app.key')),
        ], JSON_THROW_ON_ERROR));
        if ($identityIdentifier !== null) {
            try {
                Mail::to((string) $email)->queue(new PasskeyRecoveryCodeMail($language, $code));
            } catch (Throwable $exception) {
                $this->logger->error('Failed to queue passkey recovery code email.', ['exception' => $exception]);
            }
        }

        $remaining = self::MAX_SENDS - $count;

        return new EmailSendingStatus(true, $remaining, $remaining === 0 ? $windowTtl : self::COOLDOWN_SECONDS);
    }

    public function verify(Email $email, AuthCode $code): IdentityIdentifier
    {
        $key = 'passkey_recovery_email:' . $this->hash($email);
        $attemptsKey = $key . ':attempts';
        $attempts = (int) Redis::incr($attemptsKey);
        if ($attempts === 1) {
            Redis::expire($attemptsKey, self::TTL_SECONDS);
        }
        if ($attempts > self::MAX_ATTEMPTS) {
            Redis::del($key, $attemptsKey);

            throw new PasskeyRecoveryVerificationFailedException('Recovery verification attempt limit exceeded.');
        }

        $raw = Redis::get($key);
        if (! is_string($raw)) {
            throw new PasskeyRecoveryVerificationFailedException('Recovery code is invalid or expired.');
        }
        $data = json_decode($raw, true, flags: JSON_THROW_ON_ERROR);
        if (! is_array($data) || ! array_key_exists('identity_id', $data) || ! is_string($data['code_hash'] ?? null) || (! is_string($data['identity_id']) && $data['identity_id'] !== null)) {
            throw new PasskeyRecoveryVerificationFailedException();
        }
        $valid = hash_equals($data['code_hash'], hash_hmac('sha256', (string) $code, config()->string('app.key')));
        if (! $valid || $data['identity_id'] === null) {
            throw new PasskeyRecoveryVerificationFailedException('Recovery code is invalid or expired.');
        }
        if (! is_string(Redis::command('GETDEL', [$key]))) {
            throw new PasskeyRecoveryVerificationFailedException('Recovery code has already been used.');
        }
        Redis::del($attemptsKey);

        return new IdentityIdentifier($data['identity_id']);
    }

    private function hash(Email $email): string
    {
        return hash('sha256', strtolower((string) $email));
    }
}
