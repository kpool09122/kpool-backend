<?php

declare(strict_types=1);

namespace Tests\Identity\Application\Service\WebAuthn;

use PHPUnit\Framework\TestCase;
use Source\Identity\Application\Service\WebAuthn\AuthenticationVerificationInput;
use Source\Identity\Domain\ValueObject\CredentialSource;

class AuthenticationVerificationInputTest extends TestCase
{
    public function testPreservesSuppliedValues(): void
    {
        $responseJson = '{"id":"credential"}';
        $optionsJson = '{"challenge":"challenge"}';
        $credentialSource = new CredentialSource('{"type":"public-key"}');
        $expectedUserHandle = 'expectedUserHandle-value';

        $subject = new AuthenticationVerificationInput($responseJson, $optionsJson, $credentialSource, $expectedUserHandle);

        $this->assertSame($responseJson, $subject->responseJson);
        $this->assertSame($optionsJson, $subject->optionsJson);
        $this->assertSame($credentialSource, $subject->credentialSource);
        $this->assertSame($expectedUserHandle, $subject->expectedUserHandle);
    }

    public function testAllowsAbsentOptionalValues(): void
    {
        $subject = new AuthenticationVerificationInput('{"id":"credential"}', '{"challenge":"challenge"}', new CredentialSource('{"type":"public-key"}'), null);
        $this->assertNull($subject->expectedUserHandle);
    }
}
