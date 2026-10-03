<?php

declare(strict_types=1);

namespace Source\Identity\Infrastructure\Service;

use Application\Mail\SocialLinkingCodeMail;
use DateTimeImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redis;
use Source\Identity\Application\Service\EmailSendingStatus;
use Source\Identity\Application\Service\SocialLinking\SocialLinkingSession;
use Source\Identity\Application\Service\SocialLinking\SocialLinkingSessionStorageServiceInterface;
use Source\Identity\Domain\Exception\SocialLinkingSessionInvalidException;
use Source\Identity\Domain\Exception\SocialLinkingVerificationFailedException;
use Source\Identity\Domain\ValueObject\AuthCode;
use Source\Identity\Domain\ValueObject\SocialConnection;
use Source\Identity\Domain\ValueObject\SocialProvider;
use Source\Shared\Domain\Support\TypedValue;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Throwable;

class SocialLinkingSessionStorageService implements SocialLinkingSessionStorageServiceInterface
{
    private const string SESSION_KEY = 'social_linking_pending';
    private const string KEY_PREFIX = 'social_linking_pending:';
    private const string PURPOSE = 'sso.link';
    private const int TTL_SECONDS = 600;
    private const int MAX_ATTEMPTS = 5;
    private const int MAX_SENDS = 5;
    private const int COOLDOWN_SECONDS = 60;

    public function __construct(private readonly Request $request)
    {
    }

    public function issue(IdentityIdentifier $identityIdentifier, Email $email, SocialConnection $connection, string $returnTo): void
    {
        $this->assertReturnTo($returnTo);
        $binding = $this->sessionBinding();
        $oldToken = $this->request->session()->get(self::SESSION_KEY);
        if (is_string($oldToken)) {
            Redis::del(self::KEY_PREFIX . $oldToken);
        }
        $token = bin2hex(random_bytes(32));
        Redis::setex(self::KEY_PREFIX . $token, self::TTL_SECONDS, json_encode([
            'purpose' => self::PURPOSE,
            'session_binding' => $binding,
            'identity_id' => (string) $identityIdentifier,
            'email' => (string) $email,
            'provider' => $connection->provider()->value,
            'provider_user_id' => $connection->providerUserId(),
            'return_to' => $returnTo,
            'expires_at' => time() + self::TTL_SECONDS,
            'attempts' => 0,
            'sends' => 0,
            'sent_at' => 0,
            'code_hash' => null,
        ], JSON_THROW_ON_ERROR));
        $this->request->session()->put(self::SESSION_KEY, $token);
    }

    public function requireValid(): SocialLinkingSession
    {
        return $this->toSession($this->read($this->key()));
    }

    public function sendCode(Language $language): EmailSendingStatus
    {
        $key = $this->key();
        $data = $this->read($key);
        if (TypedValue::int($data['attempts']) >= self::MAX_ATTEMPTS) {
            throw new SocialLinkingVerificationFailedException();
        }

        $operationSends = TypedValue::int($data['sends']);
        if ($operationSends >= self::MAX_SENDS) {
            return new EmailSendingStatus(false, 0, null);
        }

        $now = time();
        $sendCountKey = 'social_linking_email_sends:' . hash('sha256', TypedValue::string($data['identity_id']));
        $hourlySends = TypedValue::numericInt(Redis::get($sendCountKey) ?? '0');
        $hourlyRemaining = max(0, self::MAX_SENDS - $hourlySends);
        if ($hourlyRemaining === 0) {
            return new EmailSendingStatus(false, 0, max(0, (int) Redis::ttl($sendCountKey)));
        }

        $cooldown = TypedValue::int($data['sent_at']) + self::COOLDOWN_SECONDS - $now;
        if ($cooldown > 0) {
            return new EmailSendingStatus(false, min(self::MAX_SENDS - $operationSends, $hourlyRemaining), $cooldown);
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $data['code_hash'] = $this->codeHash($code);
        $data['sent_at'] = $now;
        $data['sends'] = $operationSends + 1;
        $this->write($key, $data);
        $hourlySends = TypedValue::numericInt(Redis::incr($sendCountKey));
        if ($hourlySends === 1) {
            Redis::expire($sendCountKey, 3600);
        }
        Mail::to(TypedValue::string($data['email']))->queue(new SocialLinkingCodeMail($language, $code));

        $operationRemaining = self::MAX_SENDS - $data['sends'];
        $hourlyRemaining = self::MAX_SENDS - $hourlySends;
        $remaining = min($operationRemaining, $hourlyRemaining);
        $retryAfter = $operationRemaining === 0
            ? null
            : ($hourlyRemaining === 0 ? max(0, (int) Redis::ttl($sendCountKey)) : self::COOLDOWN_SECONDS);

        return new EmailSendingStatus(true, $remaining, $retryAfter);
    }

    public function verifyAndConsume(AuthCode $code): SocialLinkingSession
    {
        $key = $this->key();
        $data = $this->read($key);
        if (TypedValue::int($data['attempts']) >= self::MAX_ATTEMPTS) {
            Redis::del($key);

            throw new SocialLinkingVerificationFailedException();
        }
        $codeHash = $data['code_hash'] ?? null;
        if (! is_string($codeHash) || ! hash_equals($codeHash, $this->codeHash((string) $code))) {
            $data['attempts'] = TypedValue::int($data['attempts']) + 1;
            if ($data['attempts'] >= self::MAX_ATTEMPTS) {
                Redis::del($key);
            } else {
                $this->write($key, $data);
            }

            throw new SocialLinkingVerificationFailedException();
        }
        Redis::del($key);
        $this->request->session()->forget(self::SESSION_KEY);

        return $this->toSession($data);
    }

    /** @param array<array-key, mixed> $data */
    private function write(string $key, array $data): void
    {
        $remainingSeconds = TypedValue::int($data['expires_at']) - time();
        if ($remainingSeconds <= 0) {
            throw new SocialLinkingSessionInvalidException();
        }
        Redis::setex($key, $remainingSeconds, json_encode($data, JSON_THROW_ON_ERROR));
    }

    private function key(): string
    {
        $this->sessionBinding();
        $token = $this->request->session()->get(self::SESSION_KEY);
        if (! is_string($token) || preg_match('/^[a-f0-9]{64}$/D', $token) !== 1) {
            throw new SocialLinkingSessionInvalidException();
        }

        return self::KEY_PREFIX . $token;
    }

    private function sessionBinding(): string
    {
        if (! $this->request->hasSession() || $this->request->session()->getId() === '') {
            throw new SocialLinkingSessionInvalidException();
        }

        return hash_hmac('sha256', $this->request->session()->getId(), config()->string('app.key'));
    }

    private function codeHash(string $code): string
    {
        return hash_hmac('sha256', self::PURPOSE . ':' . $code, config()->string('app.key'));
    }

    /** @return array<array-key, mixed> */
    private function read(string $key): array
    {
        $raw = Redis::get($key);
        if (! is_string($raw)) {
            throw new SocialLinkingSessionInvalidException();
        }

        try {
            $data = json_decode($raw, true, flags: JSON_THROW_ON_ERROR);
            if (! is_array($data)
                || ($data['purpose'] ?? null) !== self::PURPOSE
                || ! is_string($data['session_binding'] ?? null)
                || ! hash_equals($this->sessionBinding(), $data['session_binding'])
                || ! is_int($data['expires_at'] ?? null)
                || $data['expires_at'] <= time()
                || ! is_int($data['attempts'] ?? null)
                || ! is_int($data['sends'] ?? null)
                || ! is_int($data['sent_at'] ?? null)) {
                throw new SocialLinkingSessionInvalidException();
            }
            $this->toSession($data);

            return $data;
        } catch (SocialLinkingSessionInvalidException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new SocialLinkingSessionInvalidException(previous: $exception);
        }
    }

    /** @param array<array-key, mixed> $data */
    private function toSession(array $data): SocialLinkingSession
    {
        $returnTo = TypedValue::string($data['return_to']);
        $this->assertReturnTo($returnTo);

        return new SocialLinkingSession(
            new IdentityIdentifier(TypedValue::string($data['identity_id'])),
            new Email(TypedValue::string($data['email'])),
            new SocialConnection(SocialProvider::from(TypedValue::string($data['provider'])), TypedValue::string($data['provider_user_id'])),
            $returnTo,
            new DateTimeImmutable('@' . TypedValue::int($data['expires_at'])),
        );
    }

    private function assertReturnTo(string $returnTo): void
    {
        if (! str_starts_with($returnTo, '/') || str_starts_with($returnTo, '//')
            || str_contains($returnTo, '\\') || preg_match('/[\x00-\x20\x7f]/', $returnTo) === 1) {
            throw new SocialLinkingSessionInvalidException('SSO linking return URL must be an allowed local path.');
        }
    }
}
