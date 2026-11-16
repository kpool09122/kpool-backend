<?php

declare(strict_types=1);

namespace Tests\Identity\Application\Service;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Identity\Application\Service\InternalApiToken;

class InternalApiTokenTest extends TestCase
{
    public function testCredentialRequiresExplicitRevealAndIsRedactedFromDebugAndJson(): void
    {
        $expiresAt = new DateTimeImmutable('2026-01-01T00:01:00Z');
        $token = new InternalApiToken('secret-credential', $expiresAt);
        self::assertSame('secret-credential', $token->reveal());
        self::assertSame($expiresAt, $token->expiresAt);
        self::assertSame('[REDACTED]', $token->__debugInfo()['token']);
        self::assertStringNotContainsString('secret-credential', json_encode($token, JSON_THROW_ON_ERROR));
        ob_start();
        var_dump($token);
        $debug = (string) ob_get_clean();
        self::assertStringNotContainsString('secret-credential', $debug);
        self::assertStringContainsString('[REDACTED]', $debug);
    }
}
