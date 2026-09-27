<?php

declare(strict_types=1);

namespace Source\Identity\Infrastructure\Service;

use DateTimeImmutable;
use Illuminate\Support\Facades\Redis;
use Source\Identity\Application\Service\AuthCodeSessionStorageServiceInterface;
use Source\Identity\Domain\ValueObject\AuthCode;
use Source\Identity\Domain\ValueObject\AuthCodeSession;
use Source\Shared\Domain\ValueObject\Email;

class AuthCodeSessionStorageService implements AuthCodeSessionStorageServiceInterface
{
    private const string KEY_PREFIX = 'auth_code_session:';
    private const int TTL_SECONDS = 900;

    public function findByEmail(Email $email): ?AuthCodeSession
    {
        $data = Redis::get($this->buildKey($email));

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

    public function store(AuthCodeSession $authCodeSession): void
    {
        $data = json_encode([
            'email' => (string) $authCodeSession->email(),
            'authCode' => (string) $authCodeSession->authCode(),
            'generatedAt' => $authCodeSession->generatedAt()->format(DateTimeImmutable::ATOM),
            'verifiedAt' => $authCodeSession->verifiedAt()?->format(DateTimeImmutable::ATOM),
        ]);

        Redis::setex($this->buildKey($authCodeSession->email()), self::TTL_SECONDS, $data);
    }

    public function delete(Email $email): void
    {
        Redis::del($this->buildKey($email));
    }

    private function buildKey(Email $email): string
    {
        return self::KEY_PREFIX . $email;
    }
}
