<?php

declare(strict_types=1);

namespace Tests\Shared\Infrastructure\Service\Encryption;

use Source\Shared\Application\Service\Encryption\EncryptionServiceInterface;
use Tests\TestCase;

class EncryptionServiceTest extends TestCase
{
    /**
     * 正常系：暗号文が平文と異なること.
     */
    public function testEncryptProducesDifferentString(): void
    {
        $service = $this->app->make(EncryptionServiceInterface::class);

        $plainText = 'sensitive-secret';
        $encrypted = $service->encrypt($plainText);

        $this->assertNotSame($plainText, $encrypted);
        $this->assertNotEmpty($encrypted);
    }

    /**
     * 正常系：暗号化した文字列を復号できること.
     */
    public function testEncryptCanBeDecrypted(): void
    {
        $service = $this->app->make(EncryptionServiceInterface::class);

        $plainText = 'sensitive-secret';
        $encrypted = $service->encrypt($plainText);

        $this->assertSame($plainText, $service->decrypt($encrypted));
    }
}
