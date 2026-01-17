<?php

declare(strict_types=1);

namespace Source\Shared\Infrastructure\Service\Encryption;

use Illuminate\Support\Facades\Crypt;
use Source\Shared\Application\Service\Encryption\EncryptionServiceInterface;

final class EncryptionService implements EncryptionServiceInterface
{
    public function encrypt(string $value): string
    {
        return Crypt::encryptString($value);
    }
}
