<?php

declare(strict_types=1);

namespace Source\Shared\Application\Service\Encryption;

interface EncryptionServiceInterface
{
    public function encrypt(string $value): string;

    public function decrypt(string $value): string;
}
