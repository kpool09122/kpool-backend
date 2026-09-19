<?php

declare(strict_types=1);

namespace Source\Identity\Domain\ValueObject;

use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\Foundation\StringBaseValue;

class WebAuthnChallenge extends StringBaseValue
{
    public const int MIN_BYTE_LENGTH = 32;
    public const int MAX_BYTE_LENGTH = 128;

    private const int BASE64_BLOCK_SIZE = 4;
    private const string BASE64URL_PATTERN = '/^[A-Za-z0-9_-]+$/';

    protected function validate(string $value): void
    {
        $decoded = self::decode($value);
        $byteLength = $decoded === false ? 0 : strlen($decoded);

        if ($decoded === false || $byteLength < self::MIN_BYTE_LENGTH || $byteLength > self::MAX_BYTE_LENGTH) {
            throw new InvalidArgumentException(sprintf(
                'WebAuthn challenge must be base64url encoded and contain between %d and %d bytes.',
                self::MIN_BYTE_LENGTH,
                self::MAX_BYTE_LENGTH,
            ));
        }
    }

    public function toBinary(): string
    {
        $decoded = self::decode((string) $this);
        if ($decoded === false) {
            throw new InvalidArgumentException('WebAuthn challenge is invalid.');
        }

        return $decoded;
    }

    private static function decode(string $value): string|false
    {
        if (preg_match(self::BASE64URL_PATTERN, $value) !== 1) {
            return false;
        }

        $paddingLength = (self::BASE64_BLOCK_SIZE - strlen($value) % self::BASE64_BLOCK_SIZE)
            % self::BASE64_BLOCK_SIZE;

        return base64_decode(
            strtr($value, '-_', '+/') . str_repeat('=', $paddingLength),
            true,
        );
    }
}
