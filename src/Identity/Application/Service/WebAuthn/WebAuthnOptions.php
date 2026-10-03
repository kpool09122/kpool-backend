<?php

declare(strict_types=1);

namespace Source\Identity\Application\Service\WebAuthn;

use InvalidArgumentException;
use JsonException;

readonly class WebAuthnOptions
{
    public function __construct(private string $json)
    {
        try {
            json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('WebAuthn options must be valid JSON.', previous: $exception);
        }
    }

    public function json(): string
    {
        return $this->json;
    }
}
