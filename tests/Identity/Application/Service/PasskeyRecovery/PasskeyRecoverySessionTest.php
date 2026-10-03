<?php

declare(strict_types=1);

namespace Tests\Identity\Application\Service\PasskeyRecovery;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Identity\Application\Service\PasskeyRecovery\PasskeyRecoverySession;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

class PasskeyRecoverySessionTest extends TestCase
{
    public function testPreservesSuppliedValues(): void
    {
        $identityIdentifier = new IdentityIdentifier('019c9b4c-0000-7000-8000-000000000001');
        $method = 'method-value';
        $expiresAt = new DateTimeImmutable('2026-10-03T01:02:03+00:00');

        $subject = new PasskeyRecoverySession($identityIdentifier, $method, $expiresAt);

        $this->assertSame($identityIdentifier, $subject->identityIdentifier);
        $this->assertSame($method, $subject->method);
        $this->assertSame($expiresAt, $subject->expiresAt);
    }
}
