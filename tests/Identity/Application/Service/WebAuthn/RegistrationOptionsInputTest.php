<?php

declare(strict_types=1);

namespace Tests\Identity\Application\Service\WebAuthn;

use PHPUnit\Framework\TestCase;
use Source\Identity\Application\Service\WebAuthn\RegistrationOptionsInput;
use Source\Identity\Domain\ValueObject\WebAuthnChallenge;

class RegistrationOptionsInputTest extends TestCase
{
    public function testPreservesSuppliedValues(): void
    {
        $challenge = new WebAuthnChallenge('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa');
        $userHandle = 'userHandle-value';
        $userName = 'userName-value';
        $userDisplayName = 'userDisplayName-value';
        $excludedCredentialIds = [];

        $subject = new RegistrationOptionsInput($challenge, $userHandle, $userName, $userDisplayName, $excludedCredentialIds);

        $this->assertSame($challenge, $subject->challenge);
        $this->assertSame($userHandle, $subject->userHandle);
        $this->assertSame($userName, $subject->userName);
        $this->assertSame($userDisplayName, $subject->userDisplayName);
        $this->assertSame($excludedCredentialIds, $subject->excludedCredentialIds);
    }
}
