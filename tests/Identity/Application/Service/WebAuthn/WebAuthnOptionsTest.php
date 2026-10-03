<?php

declare(strict_types=1);

namespace Tests\Identity\Application\Service\WebAuthn;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Identity\Application\Service\WebAuthn\WebAuthnOptions;

class WebAuthnOptionsTest extends TestCase
{
    public function testPreservesSuppliedValues(): void
    {
        $json = '{"challenge":"challenge"}';

        $subject = new WebAuthnOptions($json);

        $this->assertSame($json, $subject->json());
    }

    public function testRejectsMalformedJson(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new WebAuthnOptions('{invalid');
    }
}
