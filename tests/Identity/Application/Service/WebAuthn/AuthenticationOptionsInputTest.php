<?php

declare(strict_types=1);

namespace Tests\Identity\Application\Service\WebAuthn;

use PHPUnit\Framework\TestCase;
use Source\Identity\Application\Service\WebAuthn\AuthenticationOptionsInput;
use Source\Identity\Domain\ValueObject\WebAuthnChallenge;

class AuthenticationOptionsInputTest extends TestCase
{
    public function testPreservesSuppliedValues(): void
    {
        $challenge = new WebAuthnChallenge('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa');
        $allowedCredentialIds = [];

        $subject = new AuthenticationOptionsInput($challenge, $allowedCredentialIds);

        $this->assertSame($challenge, $subject->challenge);
        $this->assertSame($allowedCredentialIds, $subject->allowedCredentialIds);
    }
}
