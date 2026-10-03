<?php

declare(strict_types=1);

namespace Source\Identity\Domain\ValueObject;

use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\Foundation\StringBaseValue;

class WebAuthnCredentialId extends StringBaseValue
{
    public const int MAX_BYTE_LENGTH = 1023;

    private const int BASE64_BLOCK_SIZE = 4;
    private const string BASE64URL_PATTERN = '/^[A-Za-z0-9_-]+$/';

    protected function validate(string $value): void
    {
        $decoded = self::decode($value);

        if ($decoded === false || $decoded === '' || strlen($decoded) > self::MAX_BYTE_LENGTH) {
            throw new InvalidArgumentException(sprintf(
                'WebAuthn credential ID must be base64url encoded and contain at most %d bytes.',
                self::MAX_BYTE_LENGTH,
            ));
        }
    }

    public static function fromBinary(string $value): self
    {
        return new self(rtrim(strtr(base64_encode($value), '+/', '-_'), '='));
    }

    public function toBinary(): string
    {
        $decoded = self::decode((string) $this);
        if ($decoded === false) {
            throw new InvalidArgumentException('WebAuthn credential ID is invalid.');
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
