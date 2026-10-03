<?php

declare(strict_types=1);

namespace Tests\Identity\Application\Service\PasskeyRecovery;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Identity\Application\Service\PasskeyRecovery\PasskeyRecoveryOAuthSession;
use Source\Identity\Domain\ValueObject\SocialProvider;

class PasskeyRecoveryOAuthSessionTest extends TestCase
{
    public function testPreservesSuppliedValues(): void
    {
        $provider = SocialProvider::GOOGLE;
        $expiresAt = new DateTimeImmutable('2026-10-03T01:02:03+00:00');

        $subject = new PasskeyRecoveryOAuthSession($provider, $expiresAt);

        $this->assertSame($provider, $subject->provider);
        $this->assertSame($expiresAt, $subject->expiresAt);
    }
}
