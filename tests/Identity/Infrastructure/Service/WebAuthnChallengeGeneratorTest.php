<?php

declare(strict_types=1);

namespace Tests\Identity\Infrastructure\Service;

use PHPUnit\Framework\TestCase;
use Source\Identity\Domain\ValueObject\WebAuthnChallenge;
use Source\Identity\Infrastructure\Service\WebAuthnChallengeGenerator;

class WebAuthnChallengeGeneratorTest extends TestCase
{
    public function testGeneratesIndependentBase64UrlChallengesWithRequiredEntropy(): void
    {
        $webAuthnChallengeGenerator = new WebAuthnChallengeGenerator();
        $first = $webAuthnChallengeGenerator->generate();
        $second = $webAuthnChallengeGenerator->generate();
        $this->assertSame(WebAuthnChallenge::MIN_BYTE_LENGTH, strlen($first->toBinary()));
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9_-]+$/', (string) $first);
        $this->assertNotSame((string) $first, (string) $second);
    }
}
