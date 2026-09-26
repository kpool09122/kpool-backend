<?php

declare(strict_types=1);

namespace Source\Identity\Infrastructure\Service;

use Application\Mail\PasskeyRecoveryCodeMail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redis;
use Source\Identity\Application\Service\PasskeyRecovery\PasskeyRecoveryEmailVerificationServiceInterface;
use Source\Identity\Domain\Exception\PasskeyRecoveryVerificationFailedException;
use Source\Identity\Domain\ValueObject\AuthCode;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;

class PasskeyRecoveryEmailVerificationService implements PasskeyRecoveryEmailVerificationServiceInterface
{
    private const int TTL_SECONDS = 900;
    private const int COOLDOWN_SECONDS = 60;
    private const int MAX_SENDS = 5;
    private const int MAX_ATTEMPTS = 5;

    public function send(Email $email, ?IdentityIdentifier $identityIdentifier, Language $language): void
    {
        $hash = $this->hash($email);
        $cooldownSet = Cache::store('redis')->add(
            'passkey_recovery_email_cooldown:' . $hash,
            '1',
            self::COOLDOWN_SECONDS,
        );
        if ($cooldownSet === false) {
            return;
        }
        $countKey = 'passkey_recovery_email_sends:' . $hash;
        $count = (int) Redis::incr($countKey);
        if ($count === 1) {
            Redis::expire($countKey, 3600);
        }
        if ($count > self::MAX_SENDS) {
            return;
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        Redis::setex('passkey_recovery_email:' . $hash, self::TTL_SECONDS, json_encode([
            'identity_id' => $identityIdentifier === null ? null : (string) $identityIdentifier,
            'code_hash' => hash_hmac('sha256', $code, (string) config('app.key')),
        ], JSON_THROW_ON_ERROR));
        if ($identityIdentifier !== null) {
            Mail::to((string) $email)->send(new PasskeyRecoveryCodeMail($language, $code));
        }
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
        if (! is_array($data) || ! array_key_exists('identity_id', $data) || ! isset($data['code_hash'])) {
            throw new PasskeyRecoveryVerificationFailedException();
        }
        $valid = hash_equals((string) $data['code_hash'], hash_hmac('sha256', (string) $code, (string) config('app.key')));
        if (! $valid || $data['identity_id'] === null) {
            throw new PasskeyRecoveryVerificationFailedException('Recovery code is invalid or expired.');
        }
        if (! is_string(Redis::command('GETDEL', [$key]))) {
            throw new PasskeyRecoveryVerificationFailedException('Recovery code has already been used.');
        }
        Redis::del($attemptsKey);

        return new IdentityIdentifier((string) $data['identity_id']);
    }

    private function hash(Email $email): string
    {
        return hash('sha256', strtolower((string) $email));
    }
}
