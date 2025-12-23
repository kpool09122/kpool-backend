<?php

declare(strict_types=1);

namespace Source\Auth\Infrastructure\Repository;

use DateTimeImmutable;
use Illuminate\Support\Facades\Redis;
use Source\Auth\Domain\Entity\AuthCodeSession;
use Source\Auth\Domain\Repository\AuthCodeSessionRepositoryInterface;
use Source\Auth\Domain\ValueObject\AuthCode;
use Source\Shared\Domain\ValueObject\Email;

class AuthCodeSessionRepository implements AuthCodeSessionRepositoryInterface
{
    private const string KEY_PREFIX = 'auth_code_session:';
    private const int TTL_SECONDS = 900; // 15 minutes

    public function findByEmail(Email $email): ?AuthCodeSession
    {
        $key = $this->buildKey($email);
        $data = Redis::get($key);

        if ($data === null) {
            return null;
        }

        /** @var array{email: string, authCode: string, generatedAt: string, verifiedAt: ?string} $decoded */
        $decoded = json_decode($data, true);

        return new AuthCodeSession(
            new Email($decoded['email']),
            new AuthCode($decoded['authCode']),
            new DateTimeImmutable($decoded['generatedAt']),
            $decoded['verifiedAt'] !== null ? new DateTimeImmutable($decoded['verifiedAt']) : null,
        );
    }

    public function save(AuthCodeSession $authCodeSession): void
    {
        $key = $this->buildKey($authCodeSession->email());

        $data = json_encode([
            'email' => (string) $authCodeSession->email(),
            'authCode' => (string) $authCodeSession->authCode(),
            'generatedAt' => $authCodeSession->generatedAt()->format(DateTimeImmutable::ATOM),
            'verifiedAt' => $authCodeSession->verifiedAt()?->format(DateTimeImmutable::ATOM),
        ]);

        Redis::setex($key, self::TTL_SECONDS, $data);
    }

    public function delete(Email $email): void
    {
        $key = $this->buildKey($email);
        Redis::del($key);
    }

    private function buildKey(Email $email): string
    {
        return self::KEY_PREFIX . $email;
    }
}
